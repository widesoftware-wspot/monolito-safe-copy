<?php
namespace Wideti\DomainBundle\Service\Auth;

use Wideti\DomainBundle\Service\Auth\AuthService;

/**
 *
 * Usage: - [ setAuthService, ["@core.service.auth"] ]
 */
trait AuthServiceAware
{
    /**
     * @var AuthService
     */
    protected $authService;

    public function setAuthService(AuthService $service)
    {
        $this->authService = $service;
    }
}
