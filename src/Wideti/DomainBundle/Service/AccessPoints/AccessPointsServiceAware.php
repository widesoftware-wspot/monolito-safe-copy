<?php

namespace Wideti\DomainBundle\Service\AccessPoints;

use Wideti\DomainBundle\Service\AccessPoints\AccessPointsService;

/**
 *
 * Usage: - [ setAccessPointsService, ["@core.service.accesspoints"] ]
 */
trait AccessPointsServiceAware
{
    /**
     * @var AccessPointsService
     */
    protected $accessPointsService;

    public function setAccessPointsService(AccessPointsService $service)
    {
        $this->accessPointsService = $service;
    }
}
