<?php

namespace Wideti\DomainBundle\Service\AccessPointsGroups;

use Wideti\DomainBundle\Service\AccessPointsGroups\AccessPointsGroupsService;

/**
 *
 * Usage: - [ setAccessPointsGroupsService, ["@core.service.accesspointsgroups"] ]
 */
trait AccessPointsGroupsServiceAware
{
    /**
     * @var AccessPointsGroupsService
     */
    protected $accessPointsGroupsService;

    public function setAccessPointsGroupsService(AccessPointsGroupsService $service)
    {
        $this->accessPointsGroupsService = $service;
    }
}