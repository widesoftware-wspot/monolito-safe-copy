<?php

namespace Wideti\DomainBundle\Service\DeleteGuestsWithoutAccess;

use DateInterval;
use DateTime;
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
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\DeleteGuestFromS3Imp;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\GuestToAccountingProcessor;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\SendGuestToAccountingProcessorImp;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;

class DeleteGuestsWithoutAccessService
{
    use EntityManagerAware;
    use ElasticSearchAware;
    use RadacctRepositoryAware;
    use MongoAware;
    use LoggerAware;

    private $helperQuestion;
    private $domain;
    private $id;
    /**
     * @var GuestToAccountingProcessor|SendGuestToAccountingProcessorImp
     */
    private $accountingProcessor;

    /**
     * DeleteGuestsService constructor.
     * @param GuestToAccountingProcessor $accountingProcessor
     */
    public function __construct(GuestToAccountingProcessor $accountingProcessor)
    {
        $this->helperQuestion = new \Symfony\Component\Console\Helper\QuestionHelper();
        $this->accountingProcessor = $accountingProcessor;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<question> ############################## </question>");
        $output->writeln("<question> ### EXCLUSÃO DE VISITANTES SEM ACESSOS ### </question>");
        $output->writeln("<question> ############################## </question>");

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

        $connection = $this->mongo->getConnection()->getMongoClient();
        $db         = StringHelper::slugDomain($client->getDomain());
        $database   = $connection->selectDB($db);
        $collection = $database->guests;

        $question = new ConfirmationQuestion(
            '<info>Tem certeza que deseja realizar a exclusão? (ENTER para SIM ou N para nao)</info>',
            true
        );

        if ($this->helperQuestion->ask($input, $output, $question)) {
            $this->deleteGuests($output, $client, $collection);
        }
    }

    public function deleteGuests($output, Client $client, $collection)
    {
        $clientId = $client->getId();

        $guests = $this->em->getRepository('DomainBundle:Guests')->findBy(['client' => $client]);

        $guestsToDelete = [];

        $output->writeln("<comment>Excluindo registros do MongoDB e MySQL</comment>");

        foreach ($guests as $guest){
            $search = [
                "size" => 0,
                "query" => [
                    "term" => [
                        "username" => $guest->getId()
                    ]
                ]
            ];
            $params = [
                'index' => ElasticSearch::ALL,
                'type' => 'radacct',
                'ignore_unavailable' => true,
                'body' => $search
            ];

            $accountings = $this->elasticSearchService->searchScroll($params);

            if ($accountings['hits']['total'] == 0){
                try {
                    $this->em->getRepository('DomainBundle:Guests')->deleteByGuest($guest->getId());
                    $collection->remove(['mysql' => $guest->getId()]);
                } catch (\Exception $e) {
                    $this->logger->addError("Falha ao excluir o guest {$guest->getId()} do cliente {$clientId}");
                    $output->writeln("<error>" . $e->getMessage() . "</error>");
                    exit();
                }
            }
        }

        $output->writeln("<info>VISITANTE(s) SEM ACESSO REMOVIDO(s) COM SUCESSO!</info>");
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getMongodbServer()
    {
        return $this->mongodb_server;
    }

    public function setMongodbServer($mongodb_server)
    {
        $this->mongodb_server = $mongodb_server;
    }
}
