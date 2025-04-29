<?php

namespace Wideti\DomainBundle\Service\Guest;

use Wideti\DomainBundle\Service\Guest\GuestService;

/**
 *
 * Usage: - [ setGuestService, ["@core.service.guest"] ]
 */
trait GuestServiceAware
{
    /**
     * @var GuestService
     */
    protected $guestService;

    public function setGuestService(GuestService $service)
    {
        $this->guestService = $service;
    }
}
