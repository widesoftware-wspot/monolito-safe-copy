<?php
namespace Wideti\DomainBundle\Exception;

class EmptyRouterModeException extends \Exception
{
    protected $message = "Operation canceled, router mode not found in database.";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
