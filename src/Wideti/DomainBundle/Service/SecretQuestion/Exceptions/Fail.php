<?php

namespace Wideti\DomainBundle\Service\SecretQuestion\Exceptions;


class Fail extends \RuntimeException
{
    private $httpCode;

    public function __construct($message = "", $httpCode)
    {
        parent::__construct($message, 0);
        $this->httpCode = $httpCode;
    }

    public function getHttpCode()
    {
        return $this->httpCode;
    }
}