<?php

namespace Wideti\DomainBundle\Service\GuestNotification\Senders;

use Wideti\DomainBundle\Service\GuestNotification\Senders\EmailService;

/**
 * Usage: - [ setEmailService, ["@core.service.email"] ]
 */
trait EmailServiceAware
{
    /**
     * @var EmailService
     */
    protected $emailService;

    public function setEmailService(EmailService $service)
    {
        $this->emailService = $service;
    }
}
