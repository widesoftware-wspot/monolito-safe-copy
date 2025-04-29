<?php
namespace Wideti\DomainBundle\Exception;

class DateInvalidException extends \Exception
{
    protected $message = "Invalid date";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
