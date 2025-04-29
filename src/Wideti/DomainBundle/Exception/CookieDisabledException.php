<?php
namespace Wideti\DomainBundle\Exception;

class CookieDisabledException extends \Exception
{
    protected $message = "Please enable Cookies in your Browser";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
