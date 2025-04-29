<?php

namespace Wideti\DomainBundle\Service\SignUp;

/**
 * Usage: - [ setSignUpService, ["@core.service.signup"] ]
 */
trait SignUpServiceAware
{
    /**
     * @var SignUpService
     */
    protected $signUpService;

    public function setSignUpService(SignUpService $signUpService)
    {
        $this->signUpService = $signUpService;
    }
}