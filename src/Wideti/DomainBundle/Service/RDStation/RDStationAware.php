<?php

namespace Wideti\DomainBundle\Service\RDStation;

use Wideti\DomainBundle\Service\RDStation\RDStationService;

/**
 *
 * Usage: - [ setRdStationService, ["@core.service.rdstation"] ]
 */
trait RDStationAware
{
    /**
     * @var RDStationService
     */
    protected $rdStationService;

    /**
     * @param RDStationService $service
     */
    public function setRdStationService(RDStationService $service)
    {
        $this->rdStationService = $service;
    }
}
