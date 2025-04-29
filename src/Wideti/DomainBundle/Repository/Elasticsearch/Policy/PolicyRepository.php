<?php

namespace Wideti\DomainBundle\Repository\Elasticsearch\Policy;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\ElasticSearchIndexHelper;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;

/**
 * Class PolicyRepository
 * @package Wideti\DomainBundle\Repository\Elasticsearch\Radacct
 */
class PolicyRepository
{
    /**
     * @var ElasticSearch
     */
    private $elasticSearchService;

    /**
     * RadacctRepository constructor.
     * @param ElasticSearch $elasticSearch
     */
    public function __construct(ElasticSearch $elasticSearch)
    {
        $this->elasticSearchService = $elasticSearch;
    }

    /**
     * @param Client $client
     * @param $callingStationId
     * @return array
     */
    public function getLastPolicyByGuestMacAddress(Client $client, $callingStationId)
    {
        $query = [
            "size" => 1,
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "nested" => [
                                "path" => "client",
                                "query" => [
                                    "bool" => [
                                        "must" => [
                                            [
                                                "term" => [
                                                    "client.id" => $client->getId()
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            "nested" => [
                                "path" => "accessPoint",
                                "query" => [
                                    "bool" => [
                                        "must" => [
                                            [
                                                "term" => [
                                                    "accessPoint.callingStationId" => $callingStationId
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            "sort" => [
                "created" => [
                    "order" => "desc"
                ]
            ]
        ];

        $elasticIndex = "radius_policy_" . date('Y') . "_" . date('m');

        $result = $this->elasticSearchService->search('policy', $query, $elasticIndex);

        return empty($result['hits']['hits']) ? null : $result['hits']['hits'][0];
    }
}
