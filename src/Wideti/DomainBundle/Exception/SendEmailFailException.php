<?php

namespace Wideti\DomainBundle\Exception;

class SendEmailFailException extends \Exception
{
    protected $message = "Fail to send e-mail";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}