<?php

namespace Wideti\DomainBundle\Service\DeleteGuests;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\RadacctRepositoryAware;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearchAware;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Builder\DeleteRequestBuilder;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Builder\RequestBuilder;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\DeleteGuestFromS3Imp;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\GuestToAccountingProcessor;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\SendGuestToAccountingProcessorImp;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;

class DeleteGuestsService
{
    use EntityManagerAware;
    use ElasticSearchAware;
    use RadacctRepositoryAware;
    use MongoAware;

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
        $output->writeln("<question> ### EXCLUSÃO DE VISITANTES ### </question>");
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

        $option = $this->showPrincipalMenu($output, $this->helperQuestion, $input);

        $guest = null;

        if ($option == 1) {
            $question = new Question('<info>Digite o ID (MongoDB) do visitante que deseja excluir: </info>', null);
            $this->setId($this->helperQuestion->ask($input, $output, $question));

            if (!$this->getId()) {
                $output->writeln("<error>ID do visitante não informado!</error>");
                exit();
            }

            $guest = $collection->findOne([
                '_id' => new \MongoId($this->id)
            ]);

            if (!$guest) {
                $output->writeln("<error>Visitante não encontrado!</error>");
                exit();
            }
        }

        $question = new ConfirmationQuestion(
            '<info>Tem certeza que deseja realizar a exclusão? (ENTER para SIM ou N para nao)</info>',
            true
        );

        if ($this->helperQuestion->ask($input, $output, $question)) {
            $this->deleteGuests($output, $client, $guest, $database, $collection, $option);
        }
    }

    public function deleteGuests($output, Client $client, $guest, $database, $collection, $option)
    {
        $indexes = $this->radacctRepository->getAllIndexes($client->getId());

        if ($option == 1) {
            //ELASTIC
            $output->writeln("<comment>Excluindo registros do ElasticSearch</comment>");

            foreach ($indexes as $index) {
                try {
                    $search = [
                        "size" => 9999,
                        "query" => [
                            "term" => [
                                "username" => $guest['mysql']
                            ]
                        ]
                    ];

                    $accountings = $this->elasticSearchService->search('radacct', $search, $index['key']);

                    if ($accountings['hits']['total'] > 0) {
                        $elasticSearchObject = [];

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
                } catch (\Exception $e) {
                    $output->writeln("<error>".$e->getMessage()."</error>");
                    exit();
                }
            }

            //MONGO
            $output->writeln("<comment>Excluindo registros do MongoDB</comment>");

            try {
                $collection->remove(['_id' => new \MongoId($this->getId())]);
            } catch (\Exception $e) {
                $output->writeln("<error>".$e->getMessage()."</error>");
                exit();
            }

            //MYSQL
            $output->writeln("<comment>Excluindo registros do MySQL</comment>");

            try {
                $this->em->getRepository('DomainBundle:Guests')->deleteByGuest($guest['mysql']);
            } catch (\Exception $e) {
                $output->writeln("<error>".$e->getMessage()."</error>");
                exit();
            }

            //Accounting Processor
            $output->writeln("<comment>Excluindo registros do Accounting Processor</comment>");

            try {
                $this->accountingProcessor->process($client, $guest['mysql']);
            } catch (\Exception $e) {
                $output->writeln("<error>".$e->getMessage()."</error>");
                exit();
            }
        }

        if ($option == 2) {
            $clientId = $client->getId();

            //ELASTIC
            $output->writeln("<comment>Excluindo registros do ElasticSearch</comment>");

            foreach ($indexes as $index) {
                try {
                    $search = [
                        "size" => 9999,
                        "query" => [
                            "term" => [
                                "client_id" => $clientId
                            ]
                        ]
                    ];

                    $accountings = $this->elasticSearchService->search('radacct', $search, $index['key']);

                    if ($accountings['hits']['total'] > 0) {
                        $elasticSearchObject = [];

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
                } catch (\Exception $e) {
                    $output->writeln("<error>".$e->getMessage()."</error>");
                    exit();
                }
            }

            //MONGO
            $output->writeln("<comment>Excluindo registros do MongoDB</comment>");

            try {
                $database->dropCollection('guests');
            } catch (\Exception $e) {
                $output->writeln("<error>".$e->getMessage()."</error>");
                exit();
            }

            //MYSQL
            $output->writeln("<comment>Excluindo registros do MySQL</comment>");

            try {
                $this->em->getRepository('DomainBundle:Guests')->deleteByClient($clientId);
            } catch (\Exception $e) {
                $output->writeln("<error>".$e->getMessage()."</error>");
                exit();
            }

            //Accounting Processor
            $output->writeln("<comment>Excluindo registros do Accounting Processor</comment>");

            try {
                $guests = $this->em->getRepository('DomainBundle:Guests')->findBy(['client' => $client]);

                foreach ($guests as $guest) {
                    $this->accountingProcessor->process($client, $guest->getId());
                }
            } catch (\Exception $e) {
                $output->writeln("<error>".$e->getMessage()."</error>");
                exit();
            }
        }

        $output->writeln("<info>VISITANTE(s) REMOVIDO(s) COM SUCESSO!</info>");
    }

    public function showPrincipalMenu(
        \Symfony\Component\Console\Output\Output $output,
        \Symfony\Component\Console\Helper\QuestionHelper $questions,
        \Symfony\Component\Console\Input\ArgvInput $input
    ) {
        $domains = [1 => "Exclusão Individual", 2 => "Exclusão Completa", 3 => "Sair"];
        $output->writeln("<info>Selecione qual opção de exclusão você deseja</info>");

        foreach ($domains as $key => $value) {
            $output->writeln("<comment>" . $key . " -> " . $value . "</comment>");
        }

        $optMenu = $questions->ask($input, $output, new \Symfony\Component\Console\Question\Question(
            "Digite a opção desejada: "
        ));

        $optMenu = intval($optMenu);
        if ($optMenu >= 3 || $optMenu == 0) {
            $output->writeln("<info>Você saiu do script!</info>");
            exit;
        }

        return $optMenu;
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
