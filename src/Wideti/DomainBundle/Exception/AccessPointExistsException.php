<?php
namespace Wideti\DomainBundle\Exception;

class AccessPointExistsException extends \Exception
{
    protected $message = "Access point exists.";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
