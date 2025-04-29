<?php

namespace Wideti\DomainBundle\Service\BusinessHours;

use Wideti\DomainBundle\Service\BusinessHours\BusinessHoursService;

/**
 *
 * Usage: - [ setBusinessHoursService, ["@core.service.business_hours"] ]
 */
trait BusinessHoursServiceAware
{

    /**
     * @var BusinessHoursService
     */
    protected $businessHoursService;

    public function setBusinessHoursService(BusinessHoursService $service)
    {
        $this->businessHoursService = $service;
    }

}