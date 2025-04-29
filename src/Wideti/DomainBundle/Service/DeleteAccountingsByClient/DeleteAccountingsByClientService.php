<?php

namespace Wideti\DomainBundle\Service\DeleteAccountingsByClient;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\RadacctRepositoryAware;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearchAware;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;

use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\EventDispatcherAware;

class DeleteAccountingsByClientService extends ContainerAwareCommand
{
    use EntityManagerAware;
    use EventDispatcherAware;
    use ElasticSearchAware;
    use RadacctRepositoryAware;

    private $helperQuestion;
    private $domain;

    protected function configure()
    {
        $this->setName('accountings_client:delete')
             ->setDescription('Delete Accountings by Client');

        $this->setHelperQuestion(new QuestionHelper());
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<question> ########################################### </question>");
        $output->writeln("<question> ### EXCLUSAO DE ACCOUNTINGS DE CLIENTES ### </question>");
        $output->writeln("<question> ########################################### </question>");

        $question = new Question('<info>Digite o domain do cliente: </info>', null);
        $this->setDomain($this->getHelperQuestion()->ask($input, $output, $question));

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

        if ($this->getHelperQuestion()->ask($input, $output, $question)) {
            $this->delete($output, $client);
        }
    }

    public function delete($output, $client)
    {
        /**
         * 3) ElasticSearch
         */
        try {
            $indexes = $this->radacctRepository->getAllIndexes($client->getId());

            foreach ($indexes as $index) {
                $accountingsIds = [];

                $search = [
                    "size" => 9999,
                    "query" => [
                        "filtered" => [
                            "query" => [
                                "term" => [
                                    "client_id" => $client->getId()
                                ]
                            ]
                        ]
                    ]
                ];

                $accountings = $this->elasticSearchService->search('radacct', $search, $index['key']);

                foreach ($accountings['hits']['hits'] as $accounting) {
                    array_push($accountingsIds, $accounting['_id']);
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
        } catch (\Exception $e) {
            $output->writeln("<error>".$e->getMessage()."</error>");
            exit();
        }

        $output->writeln("<info>ACCOUNTINGS DO CLIENTE REMOVIDOS COM SUCESSO!</info>");
    }

    public function setHelperQuestion($helperQuestion)
    {
        $this->helperQuestion = $helperQuestion;
    }

    public function getHelperQuestion()
    {
        return $this->helperQuestion;
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
