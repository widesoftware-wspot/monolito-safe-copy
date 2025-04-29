<?php

namespace Wideti\DomainBundle\Service\ClientLogs;

use Exception;
use Symfony\Bridge\Monolog\Logger;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\ClientLogs\Dto\ClientLogDto;
use Wideti\DomainBundle\Service\ClientLogs\Dto\ClientOptionsGetLogDto;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;

class ClientLogServiceImp implements ClientLogsService
{
    /**
     * @var ElasticSearch
     */
    private $elasticSearch;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * ClientLogServiceImp constructor.
     * @param ElasticSearch $elasticSearch
     * @param Logger $logger
     */
    public function __construct(ElasticSearch $elasticSearch, Logger $logger)
    {
        $this->elasticSearch = $elasticSearch;
        $this->logger = $logger;
    }

    /**
     * @param ClientLogDto $log
     * @return mixed
     * @throws Exception
     */
    public function log(ClientLogDto $log)
    {
        try {
            $date     = new \DateTime();
            $index    = "erp_changelog_{$date->format('Y')}_{$date->format('m')}";
            $response = $this->elasticSearch->index(ElasticSearch::TYPE_ERP_CHANGELOG, $log->jsonSerialize(), null, $index);
        } catch (\Exception $ex) {
            $this->logger->addCritical('Fail to log ClientLog', [
                'message' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString()
            ]);
            throw new Exception($ex);
        }

        return $response->getId();
    }

    /**
     * @param ClientOptionsGetLogDto $options
     * @return array
     */
    public function getLogsBy(ClientOptionsGetLogDto $options)
    {
        $query = [
            "from" => $options->getPage() * $options->getSize(),
            "size" => $options->getSize(),
            "sort" => [
                "date" => ["order" => "desc"]
            ],
            "query" => [
                "filtered" => [
                    "filter" => [
                        "and" => [
                            "filters" => [
                                [
                                    "term" => [
                                        "clientId" => $options->getClientId()
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->elasticSearch->search("erp_logs", $query, "erp_changelog_all");
        $logs = [];
        if (isset($response["hits"]["hits"])) {
            $logs = $response["hits"]["hits"];
        }

        return $logs;
    }

    /**v
     * @param Client $client
     * @param $action
     * @return mixed|void
     * @throws Exception
     */
    public function logClientSettlementCharge(Client $client, $action)
    {
        $date = new \DateTime();
        $log = new ClientLogDto();
        $log->setClientId($client->getId())
            ->setAuthor("API")
            ->setMethod("POST")
            ->setUrl("api.wspot.com.br/clients/settlement-charge")
            ->setDate($date->format('Y-m-d H:i:s'))
            ->setAction($action)
            ->setResponse("Sucesso");
        $this->log($log);
    }

}
