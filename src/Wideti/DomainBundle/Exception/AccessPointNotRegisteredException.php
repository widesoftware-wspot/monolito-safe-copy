<?php
namespace Wideti\DomainBundle\Exception;

class AccessPointNotRegisteredException extends \Exception
{
    protected $message = "Access Point are not registered.";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
