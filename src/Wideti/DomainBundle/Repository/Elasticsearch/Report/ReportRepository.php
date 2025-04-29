<?php

namespace Wideti\DomainBundle\Repository\Elasticsearch\Report;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\ElasticSearchIndexHelper;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearchAware;

/**
 * Class ReportRepository
 * @package Wideti\DomainBundle\Repository\Elasticsearch\Report
 */
class ReportRepository
{
    /**
     * @var ElasticSearch
     */
    private $elasticSearchService;

    /**
     * ReportRepository constructor.
     * @param ElasticSearch $elasticSearch
     */
    public function __construct(ElasticSearch $elasticSearch)
    {
        $this->elasticSearchService = $elasticSearch;
    }

    public function getVisitsAndRecordsPerAccessPoint(Client $client, $period, $accessPoint, $numberOfAps)
    {
        $search = [
            "size"=> 0,
            "query"=> [
                "bool"=> [
                    "must"=> [
                        [
                            "term"=> [
                                "clientId" => $client->getId()
                            ]
                        ],
                        [
                            "range"=> [
                                "date"=> [
                                    "gte"=> $period['date_from'],
                                    "lte"=> $period['date_to']
                                ]
                            ]
                        ],
                        [
                            "bool" => [
                                "should" => $accessPoint
                            ]
                        ]
                    ]
                ]
            ],
            "aggs" => [
                "aps" => [
                    "terms" => [
                        "field" => "friendlyName",
                        "order" => [ "totalVisits" => "desc" ],
                        "size"  => $numberOfAps
                    ],
                    "aggs" => [
                        "totalVisits" => [ "sum" => [ "field" => "totalVisits" ] ],
                        "totalRegistrations" => [ "sum" => [ "field" => "totalRegistrations" ] ]
                    ]
                ]
            ]
        ];
        
        $data = $this->elasticSearchService->search(
            'report',
            $search,
            ElasticSearchIndexHelper::getReportIndex($period, ElasticSearch::REPORT_VISITS_REGISTRATIONS_PER_HOUR)
        );
        return isset($data['aggregations'])
            ? $data['aggregations']['aps']['buckets']
            : [];
    }


    public function getAllVisitsAndRegistersPerDay(Client $client, $filterDate, $accessPoint = [])
    {
        $dateFrom   = date("Y-m-d", strtotime("- 7 day"));
        $dateTo     = date("Y-m-d", strtotime("- 1 day"));

        if ($filterDate['filtered']) {
            $dateFrom   = date("Y-m-d", strtotime($filterDate['date_from']));
            $dateTo     = date("Y-m-d", strtotime($filterDate['date_to']));
        }

        $query =  [
            "size" => 0,
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "term" => [
                                "clientId" => $client->getId()
                            ]
                        ],
                        [
                            "range" => [
                                "date" => [
                                    "gte" => $dateFrom,
                                    "lte" => $dateTo
                                ]
                            ]
                        ],
                        [
                            "bool" => [
                                "should" => $accessPoint
                            ]
                        ]
                    ]
                ]
            ],
            "aggs" => [
                "visits_records_per_day" => [
                    "date_histogram" => [
                        "field" => "date",
                        "interval" => "day",
                        "format" => "dd/MM"
                    ],
                    "aggs" => [
                        "totalVisits" => [
                            "sum" => [
                                "field" => "totalVisits"
                            ]
                        ],
                        "totalRegistrations" => [
                            "sum" => [
                                "field" => "totalRegistrations"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $elasticIndex = ElasticSearchIndexHelper::getReportIndex(
            [
                'from'  => $dateFrom,
                'to'    => $dateTo
            ],
            ElasticSearch::REPORT_VISITS_REGISTRATIONS_PER_HOUR
        );

        return $this->elasticSearchService->search(
            ElasticSearch::TYPE_REPORTS,
            $query,
            $elasticIndex
        );
    }

    public function mostDataTrafficApsByClient(Client $client, $filterRangeDate = null)
    {
        $date_from  = date('Y-m-d', strtotime($filterRangeDate['date_from']));
        $date_to    = date('Y-m-d', strtotime($filterRangeDate['date_to']));

        $query = [
            "size" => 0,
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "term" => [
                                "clientId" => $client->getId()
                            ]
                        ],
                        [
                            "range" => [
                                "date" => [
                                    "gte" => $date_from,
                                    "lte" => $date_to
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            "aggs" => [
                "aps_most_data_traffic" => [
                    "terms" => [
                        "field" => "friendlyName",
                        "size"  => 10
                    ],
                    "aggs" => [
                        "traffic" => [
                            "sum" => [
                                "script" => "doc['download'].value + doc['upload'].value"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $elasticIndex = ElasticSearchIndexHelper::getReportIndex(
            [
                'from'  => $filterRangeDate['date_from'],
                'to'    => $filterRangeDate['date_to']
            ],
            ElasticSearch::REPORT_DOWNLOAD_UPLOAD
        );

        return $this->elasticSearchService->search(ElasticSearch::TYPE_REPORTS, $query, $elasticIndex);
    }


    public function mostAllAccessedHoursByClient(Client $client, $filterDate, $accessPoint = [])
    {
        $dateFrom   = date("Y-m-d", strtotime("- 7 day"));
        $dateTo     = date("Y-m-d", strtotime("- 1 day"));

        if ($filterDate['filtered']) {
            $dateFrom   = date("Y-m-d", strtotime($filterDate['date_from']));
            $dateTo     = date("Y-m-d", strtotime($filterDate['date_to']));
        }

        $search = [
            "size" => 0,
            "query" => [
                "bool" => [
                    "must" => [[
                        "term" => [
                            "clientId" => $client->getId()
                        ]
                    ], [
                        "range" => [
                            "date" => [
                                "gte" => $dateFrom,
                                "lte" => $dateTo
                            ]
                        ]
                    ], [
                        "bool" => [
                            "should" => $accessPoint
                        ]
                    ]]
                ]
            ],
            "aggs" => [
                "daily_visits" => [
                    "terms" => [
                        "size" => 0,
                        "field" => "date",
                        "format" => "dd/MM",
                        "order" => ["_term"=> "asc"]
                    ],
                    "aggs" => [
                        "totalVisits" => [
                            "sum" => [
                                "field" => "totalVisits"
                            ]
                        ]
                    ]
                ],
                "daily_registrations" => [
                    "terms" => [
                        "size" => 0,
                        "field" => "date",
                        "format" => "dd/MM",
                        "order" => ["_term"=> "asc"]
                    ],
                    "aggs" => [
                        "totalRegistrations" => [
                            "sum" => [
                                "field" => "totalRegistrations"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $data = $this->elasticSearchService->search(
            'report',
            $search,
            ElasticSearchIndexHelper::getReportIndex(
                [
                    'from'  => $dateFrom,
                    'to'    => $dateTo
                ],
                ElasticSearch::REPORT_VISITS_REGISTRATIONS_PER_HOUR
            )
        );

        return isset($data['aggregations'])
            ? $data['aggregations']
            : [];
    }

    public function mostAccessedHoursByClient(Client $client, $filterDate, $accessPoint = [])
    {
        $dateFrom   = date("Y-m-d", strtotime("- 7 day"));
        $dateTo     = date("Y-m-d", strtotime("- 1 day"));

        if ($filterDate['filtered']) {
            $dateFrom   = date("Y-m-d", strtotime($filterDate['date_from']));
            $dateTo     = date("Y-m-d", strtotime($filterDate['date_to']));
        }

        $search = [
            "size" => 0,
            "query" => [
                "bool" => [
                    "must" => [
                    	[
	                        "term" => [
	                            "clientId" => $client->getId()
	                        ]
	                    ],
	                    [
	                        "range" => [
	                            "date" => [
	                                "gte" => $dateFrom,
	                                "lte" => $dateTo
	                            ]
	                        ]
	                    ],
	                    [
	                        "bool" => [
	                            "should" => $accessPoint
	                        ]
                        ]
                    ]
                ]
            ],
            "aggs" => [
                "access_by_hour_visits" => [
                    "terms" => [
                        "size" => 0,
                        "field" => "hour",
                        "order" => [
                            "totalVisits" => "desc"
                        ]
                    ],
                    "aggs" => [
                        "totalVisits" => [
                            "sum" => [
                                "field" => "totalVisits"
                            ]
                        ]
                    ]
                ],
                "access_by_hour_registrations" => [
                    "terms" => [
                        "size" => 0,
                        "field" => "hour",
                        "order" => [
                            "totalRegistrations" => "desc"
                        ]
                    ],
                    "aggs" => [
                        "totalRegistrations" => [
                            "sum" => [
                                "field" => "totalRegistrations"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $data = $this->elasticSearchService->search(
            'report',
            $search,
            ElasticSearchIndexHelper::getReportIndex(
                [
                    'from'  => $dateFrom,
                    'to'    => $dateTo
                ],
                ElasticSearch::REPORT_VISITS_REGISTRATIONS_PER_HOUR
            )
        );

        return isset($data['aggregations'])
            ? $data['aggregations']
            : [];
    }

    public function getDownloadUploadByDate(
        Client $client,
        $period,
        $accessPoint = null,
        $downloadField,
        $uploadField,
        $interval = "day",
        $formatRange = "yyyy-MM",
        $formatAggregation = "yyyy-MM-dd"
    ) {
        $elasticIndex = ElasticSearch::REPORT_DOWNLOAD_UPLOAD_ALIAS;

        $search = [
            "query"=> [
                "filtered"=> [
                    "query"=> [
                        "term"=> ["clientId"=> $client->getId()]
                    ],

                    "filter"=> [
                        "bool"=> [
                            "must"=> [
                                [
                                    "range"=> [
                                        "date"=> [
                                            "gte"=> $period['from'],
                                            "lte"=> $period['to'],
                                            "format"=> $formatRange
                                        ]
                                    ]
                                ]
                            ],
                            "should"=> $accessPoint
                        ]
                    ]
                ]
            ],
            "size"=> 1,
            "aggs"=> [
                "download_upload"=> [
                    "date_histogram"=> [
                        "field"=> "date",
                        "interval"=> $interval,
                        "format"=> $formatAggregation,
                        "order" => ["_key" => "asc"]
                    ],
                    "aggs"=> [
                        "download"=> [
                            "sum"=> [
                                "field"=> $downloadField
                            ]
                        ],
                        "upload"=> [
                            "sum"=> [
                                "field"=> $uploadField
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $this->elasticSearchService->search('report', $search, $elasticIndex);
    }

    public function getAllIndexes($clientId, $reportAlias)
    {
        $search = [
            "size" => 0,
            "query" => [
                "term" => [
                    "clientId" => $clientId
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

        $data = $this->elasticSearchService->search('report', $search, $reportAlias);

        return isset($data['aggregations'])
            ? $data['aggregations']['indexes']['buckets']
            : [];
    }
}
