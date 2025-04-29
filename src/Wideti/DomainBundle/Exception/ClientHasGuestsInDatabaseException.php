<?php
namespace Wideti\DomainBundle\Exception;

class ClientHasGuestsInDatabaseException extends \Exception
{
    protected $message = "Operation canceled, client has guests in database.";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
