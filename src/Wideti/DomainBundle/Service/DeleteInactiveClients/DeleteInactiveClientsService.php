<?php

namespace Wideti\DomainBundle\Service\DeleteInactiveClients;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\RadacctRepositoryAware;
use Wideti\DomainBundle\Service\ClientLogs\ClientLogServiceImp;
use Wideti\DomainBundle\Service\ClientLogs\Dto\ClientLogDto;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearchAware;
use Wideti\DomainBundle\Service\Erp\ErpServiceImp;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Builder\DeleteRequestBuilder;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Builder\RequestBuilder;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\DeleteGuestFromS3Imp;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\GuestToAccountingProcessor;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\SendGuestToAccountingProcessorImp;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\WebFrameworkBundle\Aware\MongoAware;

class DeleteInactiveClientsService
{
    use EntityManagerAware;
    use ElasticSearchAware;
    use RadacctRepositoryAware;
    use LoggerAware;
    use MongoAware;

    private $bucket;
    /**
     * @var FileUpload
     */
    protected $fileUpload;
    /**
     * @var ValidatorInterface
     */
    protected $validator;
    /**
     * @var ErpServiceImp $erpServiceImp
     */
    protected $erpServiceImp;
    /**
     * @var ClientLogServiceImp $clientLogServiceImp
     */
    protected $clientLogServiceImp;
    protected $periodToDeleteInactiveClients;
    /**
     * @var GuestToAccountingProcessor|SendGuestToAccountingProcessorImp
     */
    private $accountingProcessor;

    /**
     * DeleteInactiveClientsService constructor.
     * @param $bucket
     * @param $periodToDeleteInactiveClients
     * @param $erpServiceImp
     * @param $clientLogServiceImp
     * @param GuestToAccountingProcessor $accountingProcessor
     */
    public function __construct(
        $bucket,
        $periodToDeleteInactiveClients,
        $erpServiceImp,
        $clientLogServiceImp,
        GuestToAccountingProcessor $accountingProcessor
    ) {
        $this->bucket = $bucket;
        $this->periodToDeleteInactiveClients = $periodToDeleteInactiveClients;
        $this->erpServiceImp = $erpServiceImp;
        $this->clientLogServiceImp = $clientLogServiceImp;
        $this->accountingProcessor = $accountingProcessor;
    }

    public function execute()
    {
        $clients = $this->em
            ->getRepository('DomainBundle:Client')
            ->findBy([
                'status' => Client::STATUS_INACTIVE
            ]);

        foreach ($clients as $client) {
            $dateNow     = strtotime(date('Y-m-d'));
            $lastUpdated = strtotime(date_format($client->getUpdated(), 'Y-m-d'));
            $dateDiff    = $dateNow - $lastUpdated;
            $dateDiff    = floor($dateDiff / (60 * 60 * 24));

            if ($dateDiff > $this->periodToDeleteInactiveClients) {
                try {
                    $this->em->getRepository('DomainBundle:Client')->delete($client);
                } catch (\Exception $e) {
                    $this->logger->addCritical(
                        'Inactive Clients Delete - fail to delete on MySQL - ' . $e->getMessage()
                    );
                    exit;
                }

                try {
                    $indexes = $this->radacctRepository->getAllIndexes($client->getId());

                    foreach ($indexes as $index) {
                        do {
                            $elasticSearchObject = [];

                            $search = [
                                "size" => 10000,
                                "query" => [
                                    "term" => [
                                        "client_id" => $client->getId()
                                    ]
                                ]
                            ];

                            $accountings = $this->elasticSearchService->search('radacct', $search, $index['key']);

                            if ($accountings['hits']['total'] > 0) {
                                foreach ($accountings['hits']['hits'] as $accounting) {
                                    array_push($elasticSearchObject, [
                                        'delete' => [
                                            '_index' => $index['key'],
                                            '_type'  => 'radacct',
                                            '_id'    => $accounting['_id']
                                        ]
                                    ]);
                                }

                                $this->elasticSearchService->bulk('radacct', $elasticSearchObject, $index['key']);
                            }
                        } while ($accountings['hits']['total'] > 0);
                    }
                } catch (\Exception $e) {
                    $this->logger->addCritical(
                        'Inactive Clients Delete - fail to delete on Elasticsearch - ' . $e->getMessage()
                    );
                    exit;
                }

                try {
                    $this->fileUpload->deleteAllFiles($this->bucket, $client->getDomain() . '/');
                } catch (\Exception $e) {
                    $this->logger->addCritical('Inactive Clients Delete - fail to delete on S3 - ' . $e->getMessage());
                    exit;
                }

                try {
                    $connection = $this->mongo->getConnection()->getMongoClient();
                    $db         = StringHelper::slugDomain($client->getDomain());
                    $database   = $connection->selectDB($db);
                    $database->drop();
                } catch (\Exception $e) {
                    $this->logger->addCritical(
                        'Inactive Clients Delete - fail to delete on MongoDB - ' . $e->getMessage()
                    );
                    exit;
                }

                try {
                    $this->erpServiceImp->disableClientById($client->getErpId());
                } catch (\Exception $e) {
                    $this->logger->addCritical(
                        'Inactive Clients Delete - fail to inactivate client on Superlógica - ' . $e->getMessage()
                    );
                    exit;
                }

                try {
                    $log = new ClientLogDto();
                    $log->setClientId($client->getId())
                        ->setAuthor(ClientLogDto::ORIGIN_CRON)
                        ->setMethod("")
                        ->setUrl("")
                        ->setDate(date('Y-m-d H:i:s'))
                        ->setAction("Script de desativação de cliente inativos")
                        ->setResponse("Sucesso");
                    $this->clientLogServiceImp->log($log);
                } catch (\Exception $e) {
                    $this->logger->addCritical(
                        'Inactive Clients Delete - Fail to log on elastic - ' . $e->getMessage()
                    );
                    exit;
                }

                try {
                    $guests = $this->em->getRepository('DomainBundle:Guests')->findBy(['client' => $client]);

                    foreach ($guests as $guest) {
                        $this->accountingProcessor->process($client, $guest->getId());
                    }
                } catch (\Exception $e) {
                    $this->logger->addCritical(
                        'Fail to remove Guests from Accounting Processor - ' . $e->getMessage()
                    );
                    exit;
                }
            }
        }
    }

    public function setFileUpload(FileUpload $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }

    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }
}
