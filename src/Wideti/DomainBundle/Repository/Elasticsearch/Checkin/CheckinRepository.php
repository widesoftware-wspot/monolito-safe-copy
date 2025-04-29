<?php

namespace Wideti\DomainBundle\Repository\Elasticsearch\Checkin;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearchAware;

class CheckinRepository
{
    use ElasticSearchAware;

    public function getCheckins(Client $client, array $range)
    {
        $query = [
            "size" => 0,
            "query" => [
                "filtered" => [
                    "filter" => [
                        "and" => [
                            "filters" => [
                                [
                                    "term" => [
                                        "client_id" => $client->getId()
                                    ]
                                ],
                                [
                                    "range" => [
                                        "date" => [
                                            "gte" => $range['dateFrom'],
                                            "lte" => $range['dateTo']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            "aggs" => [
                "checkins_per_page" => [
                    "terms" => [
                        "field" => "page_id"
                    ]
                ]
            ]
        ];

        return $this->elasticSearchService->search('logs', $query, 'checkins*');
    }

    public function getCheckinsPerDay(Client $client)
    {
        $now = date("Y-m-d", strtotime("- 1 day")) . " 23:59:59";

        $query = [
            "size" => 0,
            "query" => [
                "filtered" => [
                    "filter" => [
                        "and" => [
                            "filters" => [
                                [
                                    "term" => [
                                        "client_id" => $client->getId()
                                    ]
                                ],
                                [
                                    "range" => [
                                        "date" => [
                                            "gte" => "now-7d",
                                            "lte" => $now
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            "aggs" => [
                "checkins_per_day" => [
                    "date_histogram" => [
                        "field"     => "date",
                        "interval"  => "day",
                        "format"    => "dd/MM"
                    ]
                ]
            ]
        ];

        return $this->elasticSearchService->search('logs', $query, 'checkins*');
    }
}
