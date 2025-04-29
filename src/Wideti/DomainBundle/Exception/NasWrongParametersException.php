<?php
namespace Wideti\DomainBundle\Exception;

class NasWrongParametersException extends \Exception
{
    protected $message = "The parameters send by NAS is invalid.";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
