<?php
namespace Wideti\DomainBundle\Exception;

class UniqueFieldException extends \Exception
{
    protected $message = "Field already in use";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
