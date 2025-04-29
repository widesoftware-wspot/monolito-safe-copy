<?php

namespace Wideti\DomainBundle\Repository\Elasticsearch\Radacct;

use Wideti\AdminBundle\Exception\ObjectNotFoundException;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Dto\AccessPointsDto;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\ElasticSearchIndexHelper;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;

/**
 * Class RadacctRepository
 * @package Wideti\DomainBundle\Repository\Elasticsearch\Radacct
 */
class RadacctRepository
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
     * @param $clientId
     * @return mixed
     */
    public function getAllIndexes($clientId)
    {
        $search = [
            "size" => 0,
            "query" => [
                "term" => [
                    "client_id" => $clientId
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

        $indexes = $this->elasticSearchService->search('radacct', $search, ElasticSearch::ALL);

        return $indexes['aggregations']['indexes']['buckets'];
    }

    /**
     * @param int $clientId
     * @param string $reportType
     * @return array
     */
    public function getReportIndexesByClient($clientId, $reportType)
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

        $aliasName = "{$reportType}";

        $indexes = $this->elasticSearchService->search('report', $search, $aliasName);
        return $indexes['aggregations']['indexes']['buckets'];
    }

    /**
     * @param Guest $guest
     * @param $downloadMode
     * @param $uploadMode
     * @return mixed
     */
    public function getDownloadUploadByGuest(Guest $guest, $downloadMode, $uploadMode)
    {
        $search =  [
            "size" => 0,
            "query" => [
                "term" => [
                    "username" => $guest->getMysql()
                ]
            ],
            "aggs" => [
                "download" => [ "sum" => [ "field" => $downloadMode ]],
                "upload" => [ "sum" => [ "field" => $uploadMode ]]
            ]
        ];

        $downloadUpload = $this->elasticSearchService->search('radacct', $search, ElasticSearch::ALL);

        if (!isset($downloadUpload['aggregations'])) {
            return [
                'download' => [
                    'value' => 0
                ],
                'upload' => [
                    'value' => 0
                ]
            ];
        }

        return  $downloadUpload['aggregations'];
    }

    /**
     * @param Guest $guest
     * @param AccessPointsDto $accessPointsDto
     * @return bool
     */
    public function getDownloadUploadByGuestAndAccessPoint(Guest $guest, array $accessPoints, $downloadField, $uploadField)
    {
        $search =  [
            "size" => 0,
            "query" => [
                "filtered" => [
                    "filter" => [
                        "bool" => [
                            "must" => [
                                [
                                    "term" => [
                                        "username" => $guest->getMysql()
                                    ]
                                ]
                            ],
                            "should" => $accessPoints
                        ]
                    ]
                ]
            ],
            "aggs" => [
                "download" => [ "sum" => [ "field" => $downloadField ]],
                "upload" => [ "sum" => [ "field" => $uploadField ]]
            ]
        ];

        $downloadUpload = $this->elasticSearchService->search(
            "radacct",
            $search,
            ElasticSearch::ALL
        );

        if (isset($downloadUpload["aggregations"])) {
            return $downloadUpload["aggregations"];
        }

        return false;
    }

    /**
     * @param Guest $guest
     * @param string $order
     * @param int $size
     * @return bool
     */
    public function getClosedAccountingsByGuest(Guest $guest, $order = "desc", $size = 1000)
    {
        $search = [
            "size" => $size,
            "sort" => [
                "acctstarttime" => ["order" => $order]
            ],
            "query" => [
                "term" => [
                    "username" => $guest->getMysql()
                ]
            ]
        ];

        $closed = $this->elasticSearchService->search('radacct', $search, ElasticSearch::LAST_12_MONTHS);

        if ($closed["hits"]["total"] == 0) {
            return false;
        }

        return $closed["hits"]["hits"];
    }

    /**
     * @param Guest $guest
     * @return array
     */
    public function getAverageTimeAccessByGuest(Guest $guest)
    {
        $query = [
            "size" => 0,
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "exists" => [
                                "field" => "acctstoptime"
                            ]
                        ],
                        [
                            "term" => [
                                "username" => $guest->getMysql()
                            ]
                        ]
                    ]
                ]
            ],
            "aggs" => [
                "total_access_time_in_seconds" => [
                    "sum" => [
                        "script" => "doc['acctstoptime'].value - doc['acctstarttime'].value"
                    ]
                ]
            ]
        ];

        return $this->elasticSearchService->search("radacct", $query, ElasticSearch::LAST_12_MONTHS);
    }

    /**
     * @param Guest $guest
     * @return mixed
     */
    public function getTotalAccessByGuest(Guest $guest)
    {
        $search =  [
            "size" => 0,
            "query" => [
                "term" => [
                    "username" => $guest->getMysql()
                ]
            ]
        ];

        $downloadUpload = $this->elasticSearchService->search('radacct', $search, ElasticSearch::LAST_12_MONTHS);

        return $downloadUpload['hits']['total'];
    }

    /**
     * @param $acctuniqueId
     * @param $clientId
     * @return mixed
     * @throws ObjectNotFoundException
     */
    public function findByAcctUniqueId($acctuniqueId, $clientId)
    {
        $search = [
	        "query" => [
		        "bool" => [
			        "must" => [
				        [
					        "term" => [
						        "acctuniqueid" => $acctuniqueId
					        ]
				        ],
				        [
					        "term" => [
						        "client_id" => $clientId
					        ]
				        ]
			        ]
		        ]
	        ]
        ];

        $closedAccess = $this->elasticSearchService->search('radacct', $search, ElasticSearch::LAST_12_MONTHS);

        if ($closedAccess['hits']['total'] == 0) {
            throw new ObjectNotFoundException('Acesso não encontrado');
        }

        return $closedAccess['hits']['hits'][0]['_source'];
    }

	/**
	 * @param $params
	 * @param $period
	 * @param $page
	 * @param int $offset
	 * @param string $order
	 * @return array
	 * @throws \Exception
	 */
    public function findFiltered($params, $period, $page, $offset = 10, $order = "desc")
    {
        $maxReportLinesPoc  = $params['maxReportLinesPoc'];
        $filters            = $params['filters'];

        $query = [
            "from" => ($page > 1) ? ($page * $offset) - $offset : 0,
            "size" => ($maxReportLinesPoc) ? $maxReportLinesPoc : $offset,
            "sort" => [
                "acctstarttime" => ["order" => $order]
            ],
            "query" => [
                "filtered" => [
                    "filter" => [
                        "and" => [
                            "filters" => $filters
                        ]
                    ]
                ]
            ]
        ];

        return $this->elasticSearchService->search("radacct", $query, ElasticSearchIndexHelper::getIndex($period));
    }

    /**
     * @param $clientId
     * @return mixed
     */
    public function count($clientId)
    {
        $query = [
            "query" => [
                "term" => [
                    "client_id" => $clientId
                ]
            ]
        ];

        return $this->elasticSearchService->search(
            'radacct',
            $query,
            ElasticSearch::ALL
        )['hits']['total'];
    }

	/**
	 * @param $params
	 * @param $period
	 * @return mixed
	 * @throws \Exception
	 */
    public function countByQuery($params, $period)
    {
        $maxReportLinesPoc  = $params['maxReportLinesPoc'];
        $filters            = $params['filters'];

        $query = [
            "size" => ($maxReportLinesPoc) ? $maxReportLinesPoc : 0,
            "query" => [
                "filtered" => [
                    "filter" => [
                        "and" => [
                            "filters" => $filters
                        ]
                    ]
                ]
            ]
        ];

        if ($maxReportLinesPoc) {
            return $maxReportLinesPoc;
        }

        return $this->elasticSearchService->search(
            "radacct",
            $query,
            ElasticSearchIndexHelper::getIndex($period)
        )['hits']['total'];
    }

	/**
	 * @param $username
	 * @param $period
	 * @param $filterRange
	 * @return int
	 * @throws \Exception
	 */
    public function getTotalVisitsByUsername($username, $period, $filterRange)
    {
        if ($filterRange == 'created') {
            $period['to'] = date('Y-m-d H:i:s');
        }

        $search = [
            "size" => 0,
            "query" => [
                "filtered" => [
                    "query" => [
                        "match" => [
                            "username" => $username
                        ]
                    ],
                    "filter" => [
                        "bool" => [
                            "must" => [
                                "range" => [
                                    "acctstarttime" => [
                                        "gte" => $period['from'],
                                        "lte" => $period['to']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            "aggs" => [
                "total_visits" => [
                    "date_histogram" => [
                        "field" => "acctstarttime",
                        "interval" => "day",
                        "format" => "yyyy-MM-dd"
                    ]
                ]
            ]
        ];

        $accountings = $this->elasticSearchService->search(
            "radacct",
            $search,
            ElasticSearchIndexHelper::getIndex($period)
        );

        $numVisits = 0;

        foreach ($accountings['aggregations']['total_visits']['buckets'] as $bucket) {
            if ($bucket['doc_count'] > 0) {
                $numVisits++;
            }
        }

        return $numVisits;
    }

    /**
     * @param Client $client
     * @param $period
     * @param array $usersMongo
     * @param $downloadField
     * @param $uploadField
     * @param array|null $orderField
     * @param null $filterRange
     * @return array
     */
    public function getAccountingByGuests(
        Client $client,
        $period,
        array $usersMongo,
        $downloadField,
        $uploadField,
        array $orderField = null,
        $filterRange = null
    ) {
        if (empty($orderField)) {
            $orderField = [ 'count' => 'asc' ];
        }

        $must = [
            [
                "exists" => [
                    "field" => "acctstoptime"
                ]
            ]
        ];

        if ($filterRange == 'lastAccess') {
            array_push($must, [
                "range" => [
                    "acctstarttime" => [
                        "gte" => $period['from'],
                        "lte" => $period['to']
                    ]
                ]
            ]);
        }

        $search = [
            "size" => 0,
            "query" => [
                "filtered" => [
                    "query" => [
                        "match" => [
                            "client_id" => $client->getId()
                        ]
                    ],
                    "filter" => [
                        "bool" => [
                            "must" => [
                                $must
                            ],
                            "should" => $usersMongo
                        ]
                    ]
                ]
            ],
            "aggs" => [
                "guest" => [
                    "terms" => [
                        "size"  => 0,
                        "field" => "username",
                        "order" => $orderField
                    ],
                    "aggs" => [
                        "download"  => [ "sum" => [ "field" => $downloadField ]],
                        "upload"    => [ "sum" => [ "field" => $uploadField ]],
                        "count"     => [ "value_count" => [ "field" => "username" ]],
                        "averageTime" => [
                            'avg' => [
                                'script' => "(doc['acctstoptime'].value - doc['acctstarttime'].value) / 1000"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $accountingsQuery = $this->elasticSearchService->search(
            "radacct",
            $search,
            ElasticSearchIndexHelper::getIndex($period)
        );

        return $accountingsQuery;
    }

    /**
     * @param Client $client
     * @param $fieldToFilter
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @return array
     */
	public function groupByGuestWithDownloadUploadAverageTimeTotalVisits(
		Client $client,
        $fieldToFilter,
		\DateTime $dateFrom,
		\DateTime $dateTo,
        $guestsIds
	) {
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
                                $fieldToFilter => [
                                    "gte" => $dateFrom->format('Y-m-d H:i:s'),
                                    "lte" => $dateTo->format('Y-m-d H:i:s')
                                ]
                            ]
                        ]
                    ]
                ]
			],
			"aggs" => [
				"guest" => [
					"terms" => [
						"size" => 0,
						"field" => "guestId"
					],
					"aggs" => [
					    "guest_docs" => [
					        "top_hits" => [
					            "size" => 1,
                                "sort" => [
                                    [
                                        $fieldToFilter => [
                                            "order" => "desc"
                                        ]
                                    ]
                                ]
                            ]
                        ],
						"download" => [
							"sum" => [
								"field" => "download"
							]
						],
                        "upload" => [
                            "sum" => [
                                "field" => "upload"
                            ]
                        ],
                        "averageTime" => [
                            "avg" => [
                                "field" => "averageTime"
                            ]
						]
					]
				]
			]
		];
        if ($guestsIds) {
            $query["query"]["bool"]["must"][] = [
                [
                    "terms" => [
                        "guestId" => $guestsIds
                    ]
                ], 
            ];
        }

		return $this->elasticSearchService->search(
		    ElasticSearch::TYPE_REPORTS,
            $query,
            ElasticSearchIndexHelper::getReportIndex(
                [
                    'from'  => $dateFrom->format('Y-m-d H:i:s'),
                    'to'    => $dateTo->format('Y-m-d H:i:s')
                ],
                ElasticSearch::REPORT_GUESTS
            )
        );
	}

    /**
     * @param Client $client
     * @param $fieldToFilter
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param string $recurrence
     * @param array $pagedGuestsIds
     * @return array
     */
	public function retrieveGuestsIds(
		Client $client,
        $fieldToFilter,
		\DateTime $dateFrom,
		\DateTime $dateTo
	) {

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
                                $fieldToFilter => [
                                    "gte" => $dateFrom->format('Y-m-d H:i:s'),
                                    "lte" => $dateTo->format('Y-m-d H:i:s')
                                ]
                            ]
                        ]
                    ]
                ]
			],
			"aggs" => [
				"guest" => [
					"terms" => [
                        "size" => 0,
						"field" => "guestId",
					],
                ],
            ],
		];

		return $this->elasticSearchService->search(
		    ElasticSearch::TYPE_REPORTS,
            $query,
            ElasticSearchIndexHelper::getReportIndex(
                [
                    'from'  => $dateFrom->format('Y-m-d H:i:s'),
                    'to'    => $dateTo->format('Y-m-d H:i:s')
                ],
                ElasticSearch::REPORT_GUESTS,
                "aggregations.guest"
            )
        );
	}

	/**
	 * @param Client $client
	 * @param \DateTime $dateFrom
	 * @param \DateTime $dateTo
	 * @return array
	 */
	public function groupByGuest(Client $client, \DateTime $dateFrom, \DateTime $dateTo)
	{
		$query = [
			"size" => 0,
			"query" => [
				"filtered" => [
					"query" => [
						"match" => [
							"client_id" => $client->getId()
						]
					],
					"filter" => [
						"bool" => [
							"must" => [
								[
									"exists" => [
										"field" => "acctstoptime"
									]
								],
								[
									"range" => [
										"acctstarttime" => [
											"gte" => $dateFrom->format('Y-m-d H:i:s'),
											"lte" => $dateTo->format('Y-m-d H:i:s')
										]
									]
								]
							]
						]
					]
				]
			],
			"aggs" => [
				"guest" => [
					"terms" => [
						"size" => 0,
						"field" => "username"
					]
				]
			]
		];

		return $this->elasticSearchService->search('radacct', $query, ElasticSearch::LAST_MONTH);
	}

    /**
     * @param Client $client
     * @param null $filterRangeDate
     * @return array
     */
    public function mostAccessedApsByClient(Client $client, $filterRangeDate = null)
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
                                        "acctstarttime" => [
                                            "gte" => $filterRangeDate['date_from'],
                                            "lte" => $filterRangeDate['date_to']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            "aggs" => [
                "aps_mais_acessos" => [
                    "terms" => [
                        "field" => "calledstation_name",
                        "size"  => 10
                    ]
                ]
            ]
        ];

        return $this->elasticSearchService->search("radacct", $query, ElasticSearch::LAST_MONTH);
    }

    /**
     * @param AccessPoints $accessPoints
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return int
     */
    public function countTotalVisitByAp(AccessPoints $accessPoints, Client $client, \DateTime $startDate, \DateTime $endDate)
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
                                        "calledstation_name" => $accessPoints->getFriendlyName()
                                    ],
                                ],
                                [
                                    "term" => [
                                        "client_id" => $client->getId()
                                    ],
                                ],
                                [
                                    "range" => [
                                        "acctstarttime" => [
                                            "gte" => $startDate->format("Y-m-d H:i:s"),
                                            "lte" => $endDate->format("Y-m-d H:i:s")
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            "aggs" => [
                "visits_per_ap" => [
                    "terms" => [
                        "field" => "username",
                        "size" => 100000
                    ]
                ]
            ]
        ];

        $result = $this->elasticSearchService->search("radacct", $query, ElasticSearch::ALL);
        return count($result['aggregations']['visits_per_ap']["buckets"]);
    }

    /**
     * @param Client $client
     * @return \DateTime|null
     */
    public function getDateFirstAccounting(Client $client)
    {
        $query = [
            "size" => 1,
            "query" => [
                "term" => [
                    "client_id" => $client->getId()
                ]
            ],
            "sort" => [
                "acctstarttime" => [ "order" => "asc" ]
            ]
        ];
        $result = $this->elasticSearchService->search("radacct", $query, ElasticSearch::ALL);
        if (count($result['hits']['hits']) > 0) {
            $date = \DateTime::createFromFormat("Y-m-d H:i:s", $result['hits']['hits'][0]["_source"]['acctstarttime']);
            return $date ? $date : null;
        }
        return null;
    }

    /**
     * @param Client $client
     * @param null $filterRangeDate
     * @return array
     */
    public function averageConnectionTimeByClient(Client $client, $filterRangeDate = null)
    {
        $query = [
            "size" => 0,
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "exists" => [
                                "field" => "acctstoptime"
                            ]
                        ],
                        [
                            "term" => [
                                "client_id" => $client->getId()
                            ]
                        ],
                        [
                            "range" => [
                                "acctstarttime" => [
                                    "gte" => $filterRangeDate['date_from'],
                                    "lte" => $filterRangeDate['date_to']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            "aggs" => [
                "total_access_time_in_seconds" => [
                    "sum" => [
                        "script" => "doc['acctstoptime'].value - doc['acctstarttime'].value"
                    ]
                ]
            ]
        ];

        $elasticIndex = ElasticSearchIndexHelper::getIndex([
            'from'  => $filterRangeDate['date_from'],
            'to'    => $filterRangeDate['date_to']
        ]);

        return $this->elasticSearchService->search('radacct', $query, $elasticIndex);
    }

	/**
	 * @param Client $client
	 * @param $macaddress
	 * @param string $period
	 * @return array
	 * @throws \Exception
	 */
    public function getLastAccountingByApMacaddress(Client $client, $macaddress, $period = "sempre")
    {
        $elasticIndex = ElasticSearch::LAST_6_MONTHS;

        $filters = [
            [ "term" => [ "client_id" => $client->getId() ] ]
        ];

        if ($period != 'sempre') {
            $elasticIndex = ElasticSearchIndexHelper::getIndex(['period' => $period]);
            array_push($filters, [ "range" => [ "acctstoptime" => [ "gte" => "now-". $period ."d" ] ] ]);
        }

        $query = [
            "size" => 1,
            "query" => [
                "bool" => [
                    "must" => $filters
                ]
            ],
            "filter" => [
                "or" => [
                    [
                        "term" => [
                            "callingstationid" => $macaddress
                        ]
                    ],
                    [
                        "term" => [
                            "callingstationid" => strtolower($macaddress)
                        ]
                    ]
                ]
            ],
            "sort" => [
                "acctstoptime" => "desc"
            ]
        ];

        return $this->elasticSearchService->search('radacct', $query, $elasticIndex);
    }

    /**
     * @return array
     */
    public function searchDuplicatedOpenedSessions()
    {

        $interimUpdateTimeout = 45;
        $now = new \DateTime('now');
        $nowMinusTimeout = $now->sub(\DateInterval::createFromDateString("+{$interimUpdateTimeout} minutes"));

        $interimUpdateTimeout = $nowMinusTimeout->format("Y-m-d H:i:s");
        $query = [
            'size' => 20000,
            'sort' => [
                [
                    'username' => 'asc'
                ],
                [
                    'callingstationid' => 'asc'
                ],
                [
                    'acctstarttime' => 'asc'
                ],
                [
                    'framedipaddress' => 'asc'
                ]
            ],
            'query' => [
                'filtered' => [
                    'filter' => [
                        'bool' => [
                            'must' => [
                                [
                                    'missing' => [
                                        'field' => 'acctstoptime'
                                    ]
                                ],
                                [
                                    'range' => [
                                        'interim_update' => [
                                            'lte' => $interimUpdateTimeout
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $this->elasticSearchService->search('radacct', $query, ElasticSearch::LAST_3_MONTHS);
    }

    /**
     * @param $accounting
     * @param null $index
     * @return \Wideti\DomainBundle\Service\ElasticSearch\Response
     */
    public function updateCloseAccounting($accounting, $index = null)
    {
        $dateTime = date("Y-m-d H:i:s", strtotime("-1 second"));

        $elasticSearchObject = [
            'doc' => [
                'acctstoptime'      => $dateTime,
                'interim_update'    => $dateTime
            ]
        ];

        return $this->elasticSearchService->update('radacct', $accounting, $elasticSearchObject, $index);
    }

    /**
     * @param $currentId
     * @param $nextAcctstarttime
     * @param null $index
     * @return \Wideti\DomainBundle\Service\ElasticSearch\Response
     */
    public function updateAcctStopTimeSubtractingOneSecond($currentId, $nextAcctstarttime, $index = null)
    {
        $dateTime = date('Y-m-d H:i:s', strtotime($nextAcctstarttime . '-1 second'));

        $elasticSearchObject = [
            'doc' => [
                'acctstoptime'      => $dateTime,
                'interim_update'    => $dateTime
            ]
        ];

        return $this->elasticSearchService->update('radacct', $currentId, $elasticSearchObject, $index);
    }

    /**
     * @param Client $client
     * @param array $params
     * @return array
     */
    public function getOnlineAccountings(Client $client, $params = [])
    {
        $maxReportLinesPoc  = $params['maxReportLinesPoc'];
        $filters            = empty($params['filters']) ? $params : $params['filters'];

        $query = [
            "size" => ($maxReportLinesPoc) ? $maxReportLinesPoc : 9999,
            "query" => [
                "bool" => [
                    "must" => [
                        ["term" => ["client_id" => $client->getId()] ],
                        ["missing" => [
                            "field" =>  "acctstoptime"
                        ]
                        ]
                    ]
                ]
            ]
        ];

        if (isset($filters["access_point"]) && !empty($filters["access_point"])) {
            $query['query']['bool']['must'][] = [
                'match' => ['calledstation_name' => $filters["access_point"]]
            ];
        }

        if (isset($filters["username"]) && !empty($filters["username"])) {
            $query['query']['bool']['must'][] = [
                'term' => ['username' => $filters["username"]]
            ];
        }

        $result = $this->elasticSearchService->search('radacct', $query, ElasticSearch::CURRENT);
        $result = $result['hits']['hits'];
        $result = array_map(function ($row) {
            return $row['_source'];
        }, $result);
        return $result;
    }

    /**
     * @param Client $client
     * @param array $filters
     * @return mixed
     */
    public function getAccoutingsByAp(Client $client) {
        $query = [
            "size" => 0,
            "query" => [
                "bool" => [
                    "must" => [
                        ["term" => ["client_id" => $client->getId()] ],
                        [
                            "missing" => [
                                "field" =>  "acctstoptime"
                            ]
                        ]
                    ]
                ]
            ],
            "aggs" => [
                "counts" => [
                    "terms" => [
                        "field" => "calledstationid",
                        "order" => [
                            "_count" => "desc"
                        ],
                        "size" => 5000
                    ]
                ]
            ]
        ];

        $result = $this->elasticSearchService->search('radacct', $query, ElasticSearch::CURRENT);
        return $result['aggregations']['counts']['buckets'];
    }

    /**
     * @param Client $client
     * @param array $filters
     * @return mixed
     */
    public function totalOnlineAccountings(Client $client, $filters = [])
    {
        $query = [
            "size" => 0,
            "query" => [
                "bool" => [
                    "must" => [
                        ["term" => ["client_id" => $client->getId()] ],
                        [
                            "missing" => [
                                "field" =>  "acctstoptime"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        if (isset($filters["access_point"]) && !empty($filters["access_point"])) {
            $query['query']['bool']['must'][] = [
                'match' => ['calledstation_name' => $filters["access_point"]]
            ];
        }

        $result = $this->elasticSearchService->search('radacct', $query, ElasticSearch::LAST_3_MONTHS);
        return $result['hits']['total'];
    }

    /**
     * @param $params
     * @return array
     */
    public function getOnlineAccountingsByUsername($params)
    {
        $query = [
            "size" => 0,
            "query" => [
                "filtered" => [
                    "filter" => [
                        "bool" => [
                            "must" => [
                                [
                                    "missing" => [
                                        "field" => "acctstoptime"
                                    ]
                                ],
                                [
                                    "term" => [
                                        "client_id" => $params['client']
                                    ]
                                ],
                                [
                                    "term" => [
                                        "username" => $params['username']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        if (isset($params["callingstationid"]) && !empty($params["callingstationid"])) {
            $query['query']['filtered']['filter']['bool']['must'][] = [
                'term' => ['callingstationid' => $params["callingstationid"]]
            ];
        }

        $result = $this->elasticSearchService->search('radacct', $query, ElasticSearch::CURRENT);
        $result = $result['hits']['total'];
        return $result;
    }

    /**
     * @param Client $client
     * @param $callingStationId
     * @return mixed
     */
    public function getOnlineAccountingByGuestMacAddress(Client $client, $callingStationId)
    {
        $query = [
            "size" => 1,
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "missing" => [
                                "field" => "acctstoptime"
                            ]
                        ],
                        [
                            "term" => [
                                "client_id" => $client->getId()
                            ]
                        ],
                        [
                            "term" => [
                                "callingstationid" => $callingStationId
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->elasticSearchService->search('radacct', $query, ElasticSearch::CURRENT);

        if ($result['hits']['total'] < 1) return null;

        return $result['hits']['hits'][0]['_source'];
    }

    /**
     * @param $client
     * @param AccessPoints $accessPoint
     * @return array
     */
    public function checkIfAccessPointHasAccess($client, AccessPoints $accessPoint)
    {
        $identifier = $accessPoint->getIdentifier();
        $apName     = $accessPoint->getFriendlyName();

        $query = [
            "size" => 0,
            "query" => [
                "filtered" => [
                    "filter" => [
                        "bool" => [
                            "must" => [
                                [
                                    "term" => [
                                        "client_id" => $client
                                    ]
                                ]
                            ],
                            "should" => [
                                [
                                    "term" => [
                                        "calledstation_mac_address" => $identifier
                                    ]
                                ],
                                [
                                    "term" => [
                                        "calledstationid" => $identifier
                                    ]
                                ],
                                [
                                    "match" => [
                                        "calledstation_name" => $apName
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->elasticSearchService->search('radacct', $query, ElasticSearch::LAST_12_MONTHS);
        $result = $result['hits']['total'];

        return $result;
    }

    /**
     * @param $username
     * @return bool
     */
    public function isGuestOnline($username)
    {
        $query = [
            "size" => 1,
            "query" => [
                "bool" => [
                    "must_not" => [
                        [
                            "exists" => [
                                "field" => "acctstoptime"
                            ]
                        ]
                    ],
                    "must" => [
                        [
                            "term" => [
                                "username" => $username
                            ]
                        ]
                    ]
                ]
            ],
            "sort" => [
                "acctstarttime" => [
                    "order" => "desc"
                ]
            ]
        ];

        $result = $this->elasticSearchService->search('radacct', $query, ElasticSearch::CURRENT);
        return (bool) count($result['hits']['hits']);
    }

    /**
     * @param $acctuniqueid
     * @return mixed
     */
    public function getAcctIpHistoric($acctuniqueid)
    {
        $query = [
            "size" => 999,
            "query" => [
                "term" => [
                    "acctuniqueid" => $acctuniqueid
                ]
            ],
            "sort" => [
                "datetime" => "desc"
            ]
        ];

        $result = $this->elasticSearchService->search('historic', $query, ElasticSearch::ACCT_IP_HISTORIC_ALL);
        return $result['hits']['hits'];
    }

    /**
     * @param Client $client
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @return int
     */
    public function countAllVisitsPerClient(Client $client, \DateTime $dateFrom, \DateTime $dateTo)
    {
        $from = $dateFrom->format('Y-m-d 00:00:00');
        $to = $dateTo->format('Y-m-d 23:59:59');

        $query = [
            "size" => 0,
            "query" => [
                "bool" => [
                    "must" => [
                        ["term" => ["clientId" => $client->getId()]],
                        [
                            "range" => [
                                "lastAccess" => [
                                    "gte" => $from,
                                    "lte" => $to
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->elasticSearchService->search(
            'report',
            $query,
            ElasticSearchIndexHelper::getReportIndex(
                [
                    'from'  => $from,
                    'to'    => $to
                ],
                ElasticSearch::REPORT_GUESTS
            )
        );

        return $result["hits"]["total"];
    }

    /**
     * @param $client
     * @param null $guestIds
     * @param $type
     * @return array|null
     */
    public function recurringOrUniqueGuestsIds($client, $period, $guestIds = null, $type)
    {
        $range = explode('|', $period);

        $query = [
            "size" => 0,
            "query" => [
                "filtered" => [
                    "filter" => [
                        "bool" => [
                            "must" => [
                                [
                                    "term" => [
                                        "client_id" => $client
                                    ]
                                ],
                                [
                                    "range" => [
                                        "acctstarttime" => [
                                            "gte" => $range[0]." 00:00:00",
                                            "lte" => $range[1]." 23:59:59"
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            "aggs" => [
                "recurring_guests" => [
                    "terms" => [
                        "size" => 0,
                        "field" => "username"
                    ],
                    "aggs" => [
                        "visits_by_day" => [
                            "date_histogram" => [
                                "field" => "acctstarttime",
                                "interval" => "day",
                                "format" => "yyyy-MM-dd"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        if ($guestIds) {
            $query['query']['filtered']['filter']['bool']['must'][] = [
                "terms" => [
                    "username" => $guestIds
                ]
            ];
        }

        $result = $this->elasticSearchService->search(ElasticSearch::TYPE_RADACCT, $query, ElasticSearch::ALL);

        if ($result['hits']['total'] == 0) return null;

        $recurring  = [];
        $unique     = [];

        foreach ($result['aggregations']['recurring_guests']['buckets'] as $data) {
            if (count($data['visits_by_day']['buckets']) > 1) {
                array_push($recurring, $data['key']);
            } else {
                array_push($unique, $data['key']);
            }
        }

        if ($type == 'recurring') return $recurring;
        if ($type == 'unique') return $unique;
    }
}
