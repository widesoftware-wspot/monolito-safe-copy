<?php

namespace Wideti\DomainBundle\Service\Mailer\Providers;

use Wideti\DomainBundle\Service\Mailer\MailReturnStatus;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessage;

interface Provider
{
    /**
     * @param MailMessage $message
     * @return MailReturnStatus
     */
    public function send(MailMessage $message);
}
