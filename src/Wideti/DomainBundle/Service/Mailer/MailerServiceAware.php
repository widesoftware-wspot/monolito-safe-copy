<?php

namespace Wideti\DomainBundle\Service\Mailer;

/**
 * Usage: - [ setMailerService, ["@core.service.mailer"] ]
 */
trait MailerServiceAware
{
    /**
     * @var MailerService
     */
    protected $mailerService;

    public function setMailerService(MailerService $mailerService)
    {
        $this->mailerService = $mailerService;
    }
}
