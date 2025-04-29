<?php

namespace Wideti\DomainBundle\Exception;

class AWSSesValidationException extends \Exception
{
    protected $message = "Email not valid to send through AWS ses, verify if email is registered";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
