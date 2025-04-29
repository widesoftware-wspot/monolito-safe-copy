<?php

namespace Wideti\DomainBundle\Service\DeleteClient;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\RadacctRepositoryAware;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearchAware;

use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Builder\DeleteRequestBuilder;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Builder\RequestBuilder;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\GuestToAccountingProcessor;
use Wideti\DomainBundle\Service\Mail\MailHeaderServiceAware;
use Wideti\DomainBundle\Service\Mailer\MailerServiceAware;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessageBuilder;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\EventDispatcherAware;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\DomainBundle\Helpers\FileUpload;

class DeleteClientService
{
    use EntityManagerAware;
    use EventDispatcherAware;
    use ElasticSearchAware;
    use MailerServiceAware;
    use MailHeaderServiceAware;
    use RadacctRepositoryAware;
    use TwigAware;
    use MongoAware;

    private $helperQuestion;
    private $domain;
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
     * @var GuestToAccountingProcessor
     */
    private $accountingProcessor;

    /**
     * DeleteClientService constructor.
     * @param $bucket
     * @param GuestToAccountingProcessor $accountingProcessor
     */
    public function __construct($bucket, GuestToAccountingProcessor $accountingProcessor)
    {
        $this->bucket = $bucket;
        $this->helperQuestion = new \Symfony\Component\Console\Helper\QuestionHelper();
        $this->accountingProcessor = $accountingProcessor;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<question> ############################ </question>");
        $output->writeln("<question> ### EXCLUSAO DE CLIENTES ### </question>");
        $output->writeln("<question> ############################ </question>");

        $question = new Question('<info>Digite o domain do cliente que deseja excluir: </info>', null);
        $this->setDomain($this->helperQuestion->ask($input, $output, $question));

        if (!$this->getDomain()) {
            $output->writeln("<error>Informe o Dominio do cliente</error>");
            exit();
        }

        $client = $this->em
            ->getRepository('DomainBundle:Client')
            ->findOneByDomain($this->getDomain());

        if (!$client) {
            $output->writeln("<error>Cliente não existe!</error>");
            exit();
        }

        $question = new ConfirmationQuestion(
            '<info>Tem certeza que deseja remover o cliente '.$this->getDomain().' ? (ENTER para SIM ou N para nao)</info>',
            true
        );

        if ($this->helperQuestion->ask($input, $output, $question)) {
            $this->deleteClient($output, $client);
        }
    }

    public function deleteClient($output, Client $client)
    {
        $emailContent = $client;

        /**
         * Remove as informações de relatório
         **/
        try {
            $downloadUploadIndexes = $this
                ->radacctRepository
                ->getReportIndexesByClient(
                    $client->getId(),
                    ElasticSearch::REPORT_DOWNLOAD_UPLOAD_ALIAS
                );

            $registerPerApIndexes = $this
                ->radacctRepository
                ->getReportIndexesByClient(
                    $client->getId(),
                    'report_visits_registrations_per_ap'
                );

            $registerPerHourIndexes = $this
                ->radacctRepository
                ->getReportIndexesByClient(
                    $client->getId(),
                    ElasticSearch::REPORT_VISITS_REGISTRATION_PER_HOUR_ALIAS
                );

            if ($downloadUploadIndexes) {
                $this->deleteReportByClient($client->getId(), $downloadUploadIndexes);
            }

            if ($registerPerApIndexes) {
                $this->deleteReportByClient($client->getId(), $registerPerApIndexes);
            }

            if ($registerPerHourIndexes) {
                $this->deleteReportByClient($client->getId(), $registerPerHourIndexes);
            }

            $output->writeln("<info>Dados dos RELATORIOS foram removidos!</info>");
        } catch (\Exception $e) {
            $output->writeln("<error>".$e->getMessage()."</error>");
            exit();
        }

        /**
         * Remove Accountings do cliente
         */
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

            $output->writeln("<info>Dados do ELASTICSEARCH foram removidos!</info>");
        } catch (\Exception $e) {
            $output->writeln("<error>".$e->getMessage()."</error>");
            exit();
        }

        /**
         * Remove dados do S3
         */
        try {
            $this->fileUpload->deleteAllFiles($this->bucket, $client->getDomain() . '/');
            $output->writeln("<info>Dados do S3 foram removidos!</info>");
        } catch (\Exception $e) {
            $output->writeln("<error>".$e->getMessage()."</error>");
            exit();
        }

        /**
         * Remove dados do accounting processor
         */
        try {
            $this->removeGuestsFromAccountingProcessor($client);
            $output->writeln("<info>Dados do Accounting Processor foram removidos!</info>");
        } catch (\Exception $e) {
            $output->writeln("<error>".$e->getMessage()."</error>");
            exit();
        }

        /**
         * Remove do MongoDB
         */
        try {
            $connection = $this->mongo->getConnection()->getMongoClient();
            $db         = StringHelper::slugDomain($client->getDomain());
            $database   = $connection->selectDB($db);
            $database->drop();
            $output->writeln("<info>Dados do MONGODB foram removidos!</info>");
        } catch (\Exception $e) {
            $output->writeln("<error>Algum erro ocorreu ao tentar excluir o Guest no MongoDB</error>");
            $output->writeln("<error>".$e."</error>");
            exit();
        }

        /**
         * Remove do MySQL
         */
	    try {
		    $this->em->getRepository('DomainBundle:Client')->delete($client);
		    $output->writeln("<info>Dados do MYSQL foram removidos!</info>");
	    } catch (\Exception $e) {
		    $output->writeln("<error>".$e->getMessage()."</error>");
		    exit();
	    }

        $this->sendMail($emailContent);

        $output->writeln("<info>CLIENTE REMOVIDO COM SUCESSO!</info>");
    }

    /**
     * @param array $indexes
     * @param int $clientId
     */
    private function deleteReportByClient($clientId, array $indexes)
    {
        foreach ($indexes as $index) {
            do {
                $elasticSearchObject = [];

                $search = [
                    "size" => 10000,
                    "query" => [
                        "term" => [
                            "clientId" => $clientId
                        ]
                    ]
                ];

                $reports = $this->elasticSearchService->search('report', $search, $index['key']);

                if ($reports['hits']['total'] > 0) {
                    foreach ($reports['hits']['hits'] as $report) {
                        array_push($elasticSearchObject, [
                            'delete' => [
                                '_index' => $index['key'],
                                '_type'  => 'report',
                                '_id'    => $report['_id']
                            ]
                        ]);
                    }
                    $this->elasticSearchService->bulk('report', $elasticSearchObject, $index['key']);
                }
            } while ($reports['hits']['total'] > 0);
        }
    }

    private function removeGuestsFromAccountingProcessor($client)
    {
        $guests = $this->em->getRepository('DomainBundle:Guests')->findBy(['client' => $client]);

        foreach ($guests as $guest) {
            $this->accountingProcessor->process($client, $guest->getId());
        }
    }

    private function sendMail($client)
    {
        $builder = new MailMessageBuilder();
        $message = $builder
            ->subject('Exclusão de cliente - '.$client->getDomain().'.mambowifi.com')
            ->from(['WSpot' => $this->emailHeader->getSender()])
            ->to($this->emailHeader->getAdminRecipient())
            ->htmlMessage(
                $this->renderView(
                    'AdminBundle:Client:emailDeletingClient.html.twig',
                    [
                        'config' => [
                            'partner_name' => 'Mambo WiFi'
                        ],
                        'whiteLabel' => [
                            'companyName' => 'Mambo WiFi'
                        ],
                        'client' => $client
                    ]
                )
            )
            ->build()
        ;

        $this->mailerService->send($message);
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;
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
