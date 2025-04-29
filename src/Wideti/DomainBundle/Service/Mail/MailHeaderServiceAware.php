<?php

namespace Wideti\DomainBundle\Service\Mail;

use Wideti\DomainBundle\Service\Mail\MailHeaderService;

/**
 *
 * Usage: - [ setEmailHeader, ["@core.service.email_header"] ]
 */
trait MailHeaderServiceAware
{
    /**
     * @var MailHeaderService
     */
    protected $emailHeader;

    public function setEmailHeader(MailHeaderService $service)
    {
        $this->emailHeader = $service;
    }
}
