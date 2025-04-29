<?php

namespace Wideti\DomainBundle\Service\EmailConfigNas;

use Wideti\DomainBundle\Service\EmailConfigNas\EmailConfigNasService;

/**
 *
 * Usage: - [ setEmailConfigService, ["@core.service.send_nas_configuration"] ]
 */
trait EmailConfigNasAware
{
    /**
     * @var EmailConfigNasService
     */
    protected $emailConfigService;

    public function setEmailConfigService(EmailConfigNasService $service)
    {
        $this->emailConfigService = $service;
    }
}
