<?php
namespace Wideti\DomainBundle\Exception;

class ConsentErrorException extends \Exception
{
    protected $message = 'There is an error on consent server';

    public function __construct($message = null)
    {
        if (is_null($message)) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
