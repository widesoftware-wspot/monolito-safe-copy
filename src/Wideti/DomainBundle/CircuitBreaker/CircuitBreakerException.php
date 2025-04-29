<?php

namespace Wideti\DomainBundle\CircuitBreaker;

use Exception;


class CircuitBreakerException extends Exception
{
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}