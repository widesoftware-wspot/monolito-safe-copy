<?php
namespace Wideti\DomainBundle\Exception;

class InvalidEntityException extends \Exception
{
    protected $message = "Entity is invalid or fields missing";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
