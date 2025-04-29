<?php

namespace Wideti\DomainBundle\Service\ElasticSearch;

use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;

/**
 * Usage: - [ setElasticSearchService, ["@core.service.elastic_search"] ]
 */
trait ElasticSearchAware
{
    /**
     * @var ElasticSearch
     */
    protected $elasticSearchService;

    public function setElasticSearchService(ElasticSearch $service)
    {
        $this->elasticSearchService = $service;
    }
}
