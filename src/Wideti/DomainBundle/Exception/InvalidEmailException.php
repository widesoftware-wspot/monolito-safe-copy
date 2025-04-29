<?php
namespace Wideti\DomainBundle\Exception;

class InvalidEmailException extends \Exception
{
    protected $message = "This e-mail is invalid.";

    public function __construct($message = null, $code = 0)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message, $code);
    }
}
