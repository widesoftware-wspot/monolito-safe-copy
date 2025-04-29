<?php

namespace Wideti\WebFrameworkBundle\Aware;

use Wideti\WebFrameworkBundle\Service\PasswordService;

/**
 * Symfony Server Setup: - [ setPasswordService, [@web_framework.service.password] ]
 */
trait PasswordServiceAware
{
    /**
     * @var PasswordService
     */
    protected $passwordService;

    public function setPasswordService(PasswordService $service)
    {
        $this->passwordService = $service;
    }
}
