<?php
namespace Wideti\DomainBundle\Exception;

class DatabaseException extends \Exception
{
    protected $message = "An exception occur during a database operation.";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
