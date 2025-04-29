<?php

namespace Wideti\DomainBundle\Service\Radcheck;

use Wideti\DomainBundle\Service\Radcheck\RadcheckService;

/**
 *
 * Usage: - [ setRadcheckService, [@core.service.radcheck] ]
 */
trait RadcheckAware
{
    /**
     * @var RadcheckService
     */
    protected $radcheckService;

    public function setRadcheckService(RadcheckService $service)
    {
        $this->radcheckService = $service;
    }
}
