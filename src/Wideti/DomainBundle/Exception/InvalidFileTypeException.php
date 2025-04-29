<?php
namespace Wideti\DomainBundle\Exception;

class InvalidFileTypeException extends \Exception
{
    protected $message = "Invalid file type";

    public function __construct($message = null, $code = 0)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message, $code);
    }
}
