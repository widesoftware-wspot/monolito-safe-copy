<?php

namespace Wideti\DomainBundle\Repository\Elasticsearch\Radacct;

use Wideti\DomainBundle\Exception\ClientNotFoundException;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;
use Wideti\DomainBundle\Service\Radacct\Dto\AcctStreamFilterDto;

class AccountingStreamRepositoryImp implements AccountingStreamRepository
{
    /**
     * @var ElasticSearch
     */
    private $elasticSearch;

    public function __construct(ElasticSearch $elasticSearch)
    {
        $this->elasticSearch = $elasticSearch;
    }

    /**
     * @param AcctStreamFilterDto $filterDto
     * @return array
     * @throws ClientNotFoundException
     */
    public function findByFilter(AcctStreamFilterDto $filterDto)
    {
        if ($filterDto->getNextToken()) {
            return $this->elasticSearch->scroll([
                "scroll_id" => $filterDto->getNextToken(),
                "scroll"    => "60s"
            ]);
        }

        $query = QueryStreamAcctHelper::createQuery($filterDto);
        return $this
            ->elasticSearch
            ->searchScroll($query);
    }
}