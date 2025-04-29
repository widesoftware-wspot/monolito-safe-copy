<?php

namespace Wideti\DomainBundle\Service\ApiRDStation;

use Wideti\DomainBundle\Service\ApiRDStation\ApiRDStationService;

/**
 *
 * Usage: - [ setApiRDStationService, ["@core.service.api_rd_station"] ]
 */
trait ApiRDStationServiceAware
{

    /**
     * @var ApiRDStationService
     */
    protected $apiRDStationService;

    public function setApiRDStationService(ApiRDStationService $service)
    {
        $this->apiRDStationService = $service;
    }

}