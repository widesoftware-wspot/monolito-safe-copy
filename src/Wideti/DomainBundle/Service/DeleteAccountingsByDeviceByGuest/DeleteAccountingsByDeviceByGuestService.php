<?php

namespace Wideti\DomainBundle\Service\DeleteAccountingsByDeviceByGuest;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Question\ConfirmationQuestion;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\RadacctRepositoryAware;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearchAware;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;

use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\EventDispatcherAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;

class DeleteAccountingsByDeviceByGuestService extends ContainerAwareCommand
{
    use EntityManagerAware;
    use EventDispatcherAware;
    use ElasticSearchAware;
    use RadacctRepositoryAware;
    use MongoAware;

    private $helperQuestion;
    private $domain;
    private $macAddress;
    private $mysqlID;
    private $confirmation;

    protected function configure()
    {
        $this->setName('accountings_client:delete')
             ->setDescription('Delete Accountings by Device');

        $this->setHelperQuestion(new QuestionHelper());
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<question> ########################################### </question>");
        $output->writeln("<question> ### EXCLUSAO DE ACESSOS POR DEVICE POR VISITANTE ### </question>");
        $output->writeln("<question> ########################################### </question>");

        $this->setDomain($this->getHelperQuestion()->ask($input, $output, new Question('<info>Digite o domain do cliente: </info>', null)));

        if (!$this->getDomain()) {
            $output->writeln("<error>Informe o Dominio do cliente</error>");
            exit();
        }

        $this->setMacAddress($this->getHelperQuestion()->ask($input, $output, new Question('<info>Digite o mac address do device: </info>', null)));

        if (!$this->getMacAddress()) {
            $output->writeln("<error>Informe o mac address do device</error>");
            exit();
        }

        $this->setMysqlID($this->getHelperQuestion()->ask($input, $output, new Question('<info>Digite o mysql_id do visitante: </info>', null)));

        if (!$this->getMysqlID()) {
            $output->writeln("<error>Informe o mysql_id do visitante</error>");
            exit();
        }

        $client = $this->em
            ->getRepository('DomainBundle:Client')
            ->findOneByDomain($this->getDomain());

        $connection = $this->mongo->getConnection()->getMongoClient();
        $db = StringHelper::slugDomain($client->getDomain());
        $database   = $connection->selectDB($db);
        $collection = $database->guests;
        $guest = $collection->findOne(["mysql" => (int)$this->getMysqlID()]);

        if (!$client) {
            $output->writeln("<error>Cliente não existe!</error>");
            exit();
        }
        if (!$guest){
            $output->writeln("<error>Visitante não existe!</error>");
            exit();
        }


        $guestJson = json_encode($guest['properties'], JSON_UNESCAPED_UNICODE);
        $output->writeln("\n\n<info>Visitante que será excluído os acessos</info>");
        $output->writeln("<info>{$guestJson}</info>");
        $output->writeln("<info>Os acessos do device [ {$this->getMacAddress()} ] serão removidos deste usuário\n</info>\n");

        $this->setConfirmation($this->getHelperQuestion()->ask($input, $output, new Question(
            '<info>Tem certeza que deseja realizar a exclusão? (S para Sim ou N para Não)</info>',
            null
        )));

        if ($this->getConfirmation() == "S"){
            if ($this->getMacAddress() && $client != null && $this->getMysqlID()) {
                $this->removeDeviceEntries($output, $client);
                $this->delete($output, $client);
            }
        }
    }

    public function removeDeviceEntries($output, $client){
        $guest = $this->em
            ->getRepository('DomainBundle:Guests')
            ->findOneBy(["id" => $this->getMysqlID()]);
        $device = $this->em
            ->getRepository('DomainBundle:Device')
            ->findOneBy(["macAddress" => $this->getMacAddress()]);
        if ($device){
            $qb = $this->em
                ->getRepository('DomainBundle:DeviceEntry')
                ->createQueryBuilder("de");
            $qb->delete()
                ->where('de.client = :client')
                ->andWhere("de.device = :device")
                ->andWhere("de.guest = :guest")
                ->setParameter("client", $client)
                ->setParameter("device", $device)
                ->setParameter("guest", $guest)
                ->getQuery()->execute();

            $output->writeln("<info>DEVICE REMOVIDO COM SUCESSO!</info>");
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
                                "bool" => [
                                    "must" => [
                                        [
                                            "term" => [
                                                "client_id" => $client->getId()
                                            ]
                                        ],
                                        [
                                            "term" => [
                                                "callingstationid" => $this->getMacAddress()
                                            ]
                                        ],
                                        [
                                            "term" => [
                                                "username" => (int)$this->getMysqlID()
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];

                $accountings = $this->elasticSearchService->search('radacct', $search, $index['key']);

                $accountinsNotFound = true;
                foreach ($accountings['hits']['hits'] as $accounting) {
                    array_push($accountingsIds, $accounting['_id']);
                    $accountinsNotFound = false;
                }
                if ($accountinsNotFound){
                    continue;
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

        $output->writeln("<info>ACCOUNTINGS DO DEVICE REMOVIDOS COM SUCESSO!</info>");
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

    public function getMacAddress()
    {
        return $this->macAddress;
    }

    public function setMacAddress($macAddress)
    {
        $this->macAddress = $macAddress;
    }

    public function setMysqlID($mysqlID)
    {
        $this->mysqlID = $mysqlID;
    }

    public function getMysqlID()
    {
        return $this->mysqlID;
    }

    public function getConfirmation()
    {
        return $this->confirmation;
    }

    public function setConfirmation($confirmation)
    {
        $this->confirmation = $confirmation;
    }
}
