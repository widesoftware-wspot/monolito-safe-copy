<?php
namespace Wideti\DomainBundle\Exception;

class InvalidGuestIdException extends \Exception
{
    protected $message = "Invalid guest id";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
