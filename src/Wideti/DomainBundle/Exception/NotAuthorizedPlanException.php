<?php

namespace Wideti\DomainBundle\Exception;

class NotAuthorizedPlanException extends \Exception
{
    protected $message = "Not authorized plan";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
