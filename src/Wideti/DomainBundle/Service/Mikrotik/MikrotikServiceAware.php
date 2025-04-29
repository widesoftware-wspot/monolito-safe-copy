<?php

namespace Wideti\DomainBundle\Service\Mikrotik;

use Wideti\DomainBundle\Service\Mikrotik\MikrotikService;

/**
 *
 * Usage: - [ setMikrotikService, [@core.service.mikrotik] ]
 */
trait MikrotikServiceAware
{
    /**
     * @var AccessPointsGroupsService
     */
    protected $mikrotikService;

    public function setMikrotikService(MikrotikService $service)
    {
        $this->mikrotikService = $service;
    }
}
