<?php
namespace Wideti\DomainBundle\Service\SignIn;

use Wideti\DomainBundle\Service\SignIn\SignInService;

/**
 *
 * Usage: - [ setSignInService, ["@core.service.signin"] ]
 */
trait SignInServiceAware
{
    /**
     * @var SignInService
     */
    protected $signInService;

    public function setSignInService(SignInService $service)
    {
        $this->signInService = $service;
    }
}
