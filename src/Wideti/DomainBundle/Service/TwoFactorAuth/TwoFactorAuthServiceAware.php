<?php

namespace Wideti\DomainBundle\Service\TwoFactorAuth;

trait TwoFactorAuthServiceAware
{
    /**
     * @var TwoFactorAuthService $twoFactorAuthService
     */
    protected $twoFactorAuthService;

    public function setTwoFactorAuthService(TwoFactorAuthService $twoFactorAuthService)
    {
        $this->twoFactorAuthService = $twoFactorAuthService;
    }
}
