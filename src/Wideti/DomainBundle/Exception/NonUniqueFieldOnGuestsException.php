<?php
namespace Wideti\DomainBundle\Exception;

class NonUniqueFieldOnGuestsException extends \Exception
{
    protected $message = "Operation canceled, field is non unique.";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}