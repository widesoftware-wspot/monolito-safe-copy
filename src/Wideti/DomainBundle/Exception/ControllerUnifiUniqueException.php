<?php
namespace Wideti\DomainBundle\Exception;

class ControllerUnifiUniqueException extends \Exception
{
    protected $message = "Address and Port are not unique";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
