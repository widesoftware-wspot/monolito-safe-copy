<?php
namespace Wideti\DomainBundle\Exception;

class GuestNotFoundException extends \Exception
{
    protected $message = "Guest not found on collection";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
