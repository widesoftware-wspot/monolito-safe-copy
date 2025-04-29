<?php

namespace Wideti\DomainBundle\Service\TwoFactorAuth\Dto;

class ResponseAuthorization
{
    /**
     * @var bool
     */
    private $isAuthorized;
    private $message;

    public function __construct($isAuthorized, $message)
    {
        $this->isAuthorized = $isAuthorized;
        $this->message = $message;
    }

    /**
     * @return boolean
     */
    public function isAuthorized()
    {
        return $this->isAuthorized;
    }

    /**
     * @param boolean $isAuthorized
     */
    public function setAuthorized($isAuthorized)
    {
        $this->isAuthorized = $isAuthorized;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }
}
