<?php

namespace Wideti\DomainBundle\Service\DeleteGuestsAndAccountings;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\RadacctRepositoryAware;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearchAware;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Builder\DeleteRequestBuilder;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Builder\RequestBuilder;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\GuestToAccountingProcessor;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\SendGuestToAccountingProcessorImp;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\EventDispatcherAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;

class DeleteGuestsAndAccountingsService
{
    use EntityManagerAware;
    use EventDispatcherAware;
    use ElasticSearchAware;
    use RadacctRepositoryAware;
    use MongoAware;

    private $helperQuestion;
    private $domain;
    private $dateFrom;
    private $dateTo;
    /**
     * @var GuestToAccountingProcessor
     */
    private $accountingProcessor;

    /**
     * DeleteGuestsAndAccountingsService constructor.
     * @param GuestToAccountingProcessor $accountingProcessor
     */
    public function __construct(GuestToAccountingProcessor $accountingProcessor)
    {
        $this->helperQuestion = new \Symfony\Component\Console\Helper\QuestionHelper();
        $this->accountingProcessor = $accountingProcessor;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<question> ######################################## </question>");
        $output->writeln("<question> ### EXCLUSAO DE GUESTS E ACCOUNTINGS ### </question>");
        $output->writeln("<question> ######################################## </question>");

	    $question = new Question('<info>Digite o domain do cliente: </info>', null);
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
            '<info>Tem certeza que deseja remover TODOS os visitantes e accountings do cliente '.$this->getDomain().'?
            (ENTER para SIM ou N para nao)</info>',
            true
        );

        if ($this->helperQuestion->ask($input, $output, $question)) {
            $this->delete($output, $client);
        }
    }

    public function delete($output, $client)
    {
	    try {
		    /**
		     * 1) Monta um array com os IDs dos visitantes
		     */
		    $guestIds = $this->guestsIds($output, $client);

            /**
		     * 2) MySQL
		     */
		    $this->removeGuestsMySQL($output, $client);

		    /**
		     * 3) ElasticSearch
		     */
		    $this->removeAcctsElastic($output, $client, $guestIds);

		    /**
		     * 4) Relatórios pré processados
		     */
        	$this->removeReports($output, $client, 'report_download_upload');
        	$this->removeReports($output, $client, 'report_visits_registrations_per_ap');
        	$this->removeReports($output, $client, 'report_visits_registrations_per_hour');
            $this->removeReports($output, $client, 'report_guests');

            /**
             * 5) MongoDB
             */
            $this->removeGuestsMongoDB($output, $client, $guestIds);

            /**
             * 6) Accounting Processor
             */
            $this->removeGuestsFromAccountingProcessor($output, $client, $guestIds);
        } catch (\Exception $e) {
            $output->writeln("<error>".$e->getMessage()."</error>");
            exit();
        }

        $output->writeln("<info>GUESTS E ACCOUNTINGS REMOVIDOS COM SUCESSO!</info>");
    }

    private function guestsIds($output, $client)
    {
        $guestIds = [];

        try {
            $connection = $this->mongo->getConnection()->getMongoClient();
            $db         = StringHelper::slugDomain($client->getDomain());
            $database   = $connection->selectDB($db);
            $collection = $database->guests;

            $guests = $collection->find();

            foreach ($guests as $guest) {
                array_push($guestIds, $guest['mysql']);
            }

        } catch (\Exception $e) {
            $output->writeln("<error>Algum erro ocorreu ao montar o array com os IDs dos visitantes no MongoDB</error>");
            $output->writeln("<error>".$e."</error>");
            exit();
        }

        return $guestIds;
    }

    private function removeGuestsMongoDB($output, $client, $guestIds)
    {
	    try {
		    $connection = $this->mongo->getConnection()->getMongoClient();
		    $db         = StringHelper::slugDomain($client->getDomain());
		    $database   = $connection->selectDB($db);
		    $collection = $database->guests;

		    foreach ($guestIds as $id) {
                $collection->remove([
                    'mysql' => $id
                ]);
		    }
	    } catch (\Exception $e) {
		    $output->writeln("<error>Algum erro ocorreu ao tentar excluir os Guests no MongoDB</error>");
		    $output->writeln("<error>".$e."</error>");
		    exit();
	    }

	    $output->writeln("<info>Removeu os visitantes do MongoDB</info>");
    }

    private function removeGuestsMySQL($output, $client)
    {
	    try {
            $this->em->getRepository('DomainBundle:Client')->deleteAllGuestsAndAccountings($client);
	    } catch (\Exception $e) {
		    $output->writeln("<error>".$e->getMessage()."</error>");
		    exit();
	    }

	    $output->writeln("<info>Removeu os visitantes do MySQL</info>");
    }

    private function removeAcctsElastic($output, $client, $guestIds)
    {
	    try {
		    if (!empty($guestIds)) {
			    $indexes = $this->radacctRepository->getAllIndexes($client->getId());

			    foreach ($indexes as $index) {
				    $accountingsIds = [];

				    foreach ($guestIds as $guestId) {
					    $search = [
						    "size" => 9999,
						    "query" => [
							    "filtered" => [
								    "query" => [
									    "term" => [
										    "username" => $guestId
									    ]
								    ]
							    ]
						    ]
					    ];

					    $accountings = $this->elasticSearchService->search('radacct', $search, $index['key']);

					    foreach ($accountings['hits']['hits'] as $accounting) {
						    array_push($accountingsIds, $accounting['_id']);
					    }
				    }

				    $elasticSearchObject = [];

				    foreach ($accountingsIds as $id) {
					    array_push($elasticSearchObject, [
						    'delete' => [
							    '_index' => $index['key'],
							    '_type'  => 'radacct',
							    '_id'    => $id
						    ]
					    ]);
				    }

				    $this->elasticSearchService->bulk('radacct', $elasticSearchObject, $index['key']);
			    }

		    }
	    } catch (\Exception $e) {
		    $output->writeln("<error>".$e->getMessage()."</error>");
		    exit();
	    }

	    $output->writeln("<info>Removeu os accountings do Elastic</info>");
    }

	private function removeReports($output, $client, $report)
	{
		$search = [
			"size" => 0,
			"query" => [
				"term" => [
					"clientId" => $client->getId()
				]
			],
			"aggs" => [
				"indexes" => [
					"terms" => [
						"field" => "_index",
						"size"  => 1000
					]
				]
			]
		];

		$indexes = $this->elasticSearchService->search('report', $search, "{$report}_all");

		if ($indexes['hits']['total'] > 0) {
            $indexes = $indexes['aggregations']['indexes']['buckets'];

            foreach ($indexes as $index) {
                $recordsIds = [];

                $search = [
                    "size" => 9999,
                    "query" => [
                        "term" => [
                            "clientId" => $client->getId()
                        ]
                    ]
                ];

                $records = $this->elasticSearchService->search('report', $search, $index['key']);

                foreach ($records['hits']['hits'] as $record) {
                    array_push($recordsIds, $record['_id']);
                }

                $elasticSearchObject = [];

                foreach ($recordsIds as $id) {
                    array_push($elasticSearchObject, [
                        'delete' => [
                            '_index' => $index['key'],
                            '_type' => 'report',
                            '_id' => $id
                        ]
                    ]);
                }
                $this->elasticSearchService->bulk('report', $elasticSearchObject, $index['key']);
            }

            $output->writeln("<info>Removeu os relatórios pré processados do Elastic [{$report}]</info>");
        }
	}

    private function removeGuestsFromAccountingProcessor($output, $client, $guestIds)
    {
        foreach ($guestIds as $guestId) {
            $this->accountingProcessor->process($client, $guestId);
        }

        $output->writeln("<info>Removeu os arquivos do S3</info>");
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;
    }
}
