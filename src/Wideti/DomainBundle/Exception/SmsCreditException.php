<?php
namespace Wideti\DomainBundle\Exception;

class SmsCreditException extends \Exception
{
    protected $message = 'SMS Credit Exception';

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
