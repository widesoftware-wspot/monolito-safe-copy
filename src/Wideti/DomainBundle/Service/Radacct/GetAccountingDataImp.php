<?php

namespace Wideti\DomainBundle\Service\Radacct;

use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;
use Wideti\DomainBundle\Service\Radacct\Helper\ParseStreamResultToDtoHelper;

/**
 * Class GetAccountingDataImp
 * @package Wideti\DomainBundle\Service\Radacct
 */
class GetAccountingDataImp implements GetAccountingData
{
    /**
     * @var ElasticSearch
     */
    private $elasticSearch;

    /**
     * GetAccountingDataImp constructor.
     * @param ElasticSearch $elasticSearch
     */
    public function __construct(ElasticSearch $elasticSearch)
    {
        $this->elasticSearch = $elasticSearch;
    }

    /**
     * @param Request $request
     * @return array|mixed
     */
    public function get(Request $request)
    {
        $jsonData = json_decode($request->getContent(), true);

        if (empty($jsonData)) {
            return [];
        }
        $idList = $jsonData['ids'];
        $search = [
            "query" => [
                "terms" => [
                    "_id" => $idList
                ]
            ]
        ];

        $result = $this->elasticSearch
            ->search('radacct', $search, ElasticSearch::ALL);

        return (!empty($result)) ?
            ParseStreamResultToDtoHelper::parse($result) : [];
    }
}