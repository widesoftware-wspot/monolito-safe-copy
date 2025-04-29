<?php

namespace Wideti\DomainBundle\Repository\Elasticsearch\Radacct;

/**
 *
 * Usage: - [ setRadacctRepository, ["@core.repository.elasticsearch.radacct"] ]
 */
trait RadacctRepositoryAware
{
    /**
     * @var RadacctRepository
     */
    protected $radacctRepository;

    public function setRadacctRepository(RadacctRepository $radacctRepository)
    {
        $this->radacctRepository = $radacctRepository;
    }
}
