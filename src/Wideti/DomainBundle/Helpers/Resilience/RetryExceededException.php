<?php


namespace Wideti\DomainBundle\Helpers\Resilience;

use Exception;

class RetryExceededException extends Exception
{
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}