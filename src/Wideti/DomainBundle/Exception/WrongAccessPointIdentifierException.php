<?php
namespace Wideti\DomainBundle\Exception;

class WrongAccessPointIdentifierException extends \Exception
{
    protected $message = "Identifier is wrong";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
