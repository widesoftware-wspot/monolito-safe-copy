<?php

namespace Wideti\DomainBundle\Service\GuestSocial;

use Wideti\DomainBundle\Service\GuestSocial\GuestSocialService;

/**
 *
 * Usage: - [ setGuestSocialService, [@core.service.guestSocial] ]
 */
trait GuestSocialServiceAware
{
    /**
     * @var GuestSocialService
     */
    protected $guestSocialService;

    public function setGuestSocialService(GuestSocialService $service)
    {
        $this->guestSocialService = $service;
    }
}
