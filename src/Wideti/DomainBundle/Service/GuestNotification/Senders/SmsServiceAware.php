<?php

namespace Wideti\DomainBundle\Service\GuestNotification\Senders;

use Wideti\DomainBundle\Service\GuestNotification\Senders\SmsService;

/**
 * Usage: - [ setSmsService, [@core.service.sms] ]
 */
trait SmsServiceAware
{
    /**
     * @var SmsService
     */
    protected $smsService;

    public function setSmsService(SmsService $service)
    {
        $this->smsService = $service;
    }
}
