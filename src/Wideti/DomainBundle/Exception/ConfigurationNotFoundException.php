<?php
namespace Wideti\DomainBundle\Exception;

class ConfigurationNotFoundException extends \Exception
{
    public function __construct($message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct($message, $code, $previous);
    }
}
