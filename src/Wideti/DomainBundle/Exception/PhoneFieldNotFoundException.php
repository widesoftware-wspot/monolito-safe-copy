<?php
namespace Wideti\DomainBundle\Exception;

class PhoneFieldNotFoundException extends \Exception
{
    protected $message = "Phone or Mobile Field not found";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
