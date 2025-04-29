<?php


namespace Wideti\DomainBundle\Exception;


class AccessPointsGroupsConfigurationsException extends \Exception
{
    protected $message = "Fail to save access points groups configurations.";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}