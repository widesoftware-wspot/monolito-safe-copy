<?php

namespace Wideti\DomainBundle\Service\SecretQuestion\Exceptions;

class Forbidden extends Fail
{
    private $attemptsAvailable;

    public function __construct($message = "", $retryAttempts, $httpCode = 0)
    {
        parent::__construct($message, $httpCode);
        $this->attemptsAvailable = 5 - $retryAttempts;
    }

    public function getAttemptsAvailable()
    {
        return $this->attemptsAvailable;
    }
}