<?php
namespace Wideti\DomainBundle\Exception;

class ErrorOnCreateAccessPointException extends \Exception
{
    protected $message = "Occurred some error on create the access point.";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
