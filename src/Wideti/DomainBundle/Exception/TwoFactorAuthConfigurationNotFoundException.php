<?php
namespace Wideti\DomainBundle\Exception;

class TwoFactorAuthConfigurationNotFoundException extends \Exception
{
    protected $message = "TwoFactorAuth configuration not found";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}
