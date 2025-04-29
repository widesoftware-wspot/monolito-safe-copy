<?php
namespace Wideti\DomainBundle\Exception;

class DuplicateDomainException extends \Exception
{
    protected $message = "This domain already exists.";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
