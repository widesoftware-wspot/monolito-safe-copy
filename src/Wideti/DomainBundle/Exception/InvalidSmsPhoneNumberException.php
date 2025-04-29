<?php
namespace Wideti\DomainBundle\Exception;

class InvalidSmsPhoneNumberException extends \Exception
{
    protected $message = "This phone number is invalid to send SMS.";

    public function __construct($message = null, $code = 0)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message, $code);
    }
}
