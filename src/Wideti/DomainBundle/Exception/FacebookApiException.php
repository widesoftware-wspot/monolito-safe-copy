<?php
namespace Wideti\DomainBundle\Exception;

class FacebookApiException extends \Exception
{
    protected $message = "Something went wrong with Facebook API";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
