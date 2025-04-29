<?php
namespace Wideti\DomainBundle\Exception;

class InvalidDocumentException extends \Exception
{
    protected $message = "Document is invalid or fields missing";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
