<?php

namespace Wideti\DomainBundle\Service\Blacklist;

use Wideti\DomainBundle\Service\Blacklist\BlacklistService;

/**
 * Usage: - [ setBlacklistService, ["@core.service.blacklist"] ]
 */
trait BlacklistServiceAware
{
    /**
     * @var BlacklistService
     */
    protected $blacklistService;

    public function setBlacklistService(BlacklistService $service)
    {
        $this->blacklistService = $service;
    }
}