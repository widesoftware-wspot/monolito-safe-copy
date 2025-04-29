<?php

namespace Wideti\DomainBundle\Repository\Elasticsearch\Checkin;

/**
 *
 * Usage: - [ setCheckinRepository, ["@core.repository.elasticsearch.checkin"] ]
 */
trait CheckinRepositoryAware
{
    /**
     * @var CheckinRepository
     */
    protected $checkinRepository;

    public function setRadacctRepository(CheckinRepository $repository)
    {
        $this->checkinRepository = $repository;
    }
}
