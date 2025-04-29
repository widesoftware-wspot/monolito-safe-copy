<?php

namespace Wideti\DomainBundle\Dto;


class PurchaseLoginDto
{
    /** @var bool */
    private $loginSuccess;

    public function __construct()
    {
        $this->loginSuccess = false;
    }

    /** @param bool $loginSuccess */
    public function setLoginSuccess($loginSuccess)
    {
        $this->loginSuccess = $loginSuccess;
    }

    /** @return bool */
    public function isLoginSuccess()
    {
        return $this->loginSuccess;
    }
}
