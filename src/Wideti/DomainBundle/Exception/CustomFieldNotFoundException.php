<?php
namespace Wideti\DomainBundle\Exception;

class CustomFieldNotFoundException extends \Exception
{
    protected $message = "Custom field not found";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
