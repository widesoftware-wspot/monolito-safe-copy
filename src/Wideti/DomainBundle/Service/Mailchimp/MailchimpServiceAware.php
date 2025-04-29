<?php

namespace Wideti\DomainBundle\Service\Mailchimp;

use Wideti\DomainBundle\Service\Mailchimp\MailchimpService;

/**
 *
 * Usage: - [ setMailchimpService, ["@core.service.mailchimp"] ]
 */
trait MailchimpServiceAware
{
    /**
     * @var MailchimpService
     */
    protected $mailchimpService;

    public function setMailchimpService(MailchimpService $service)
    {
        $this->mailchimpService = $service;
    }
}
