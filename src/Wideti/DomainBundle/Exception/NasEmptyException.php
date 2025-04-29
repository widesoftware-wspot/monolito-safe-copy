<?php
namespace Wideti\DomainBundle\Exception;

class NasEmptyException extends \Exception
{
    protected $message = "Vendor name is empty on Nas Factory.";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
