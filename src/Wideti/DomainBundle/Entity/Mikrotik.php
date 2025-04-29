<?php

namespace Wideti\DomainBundle\Entity;

class Mikrotik
{
    protected $ssid;

    protected $identity;

    /**
     * @param mixed $identity
     */
    public function setIdentity($identity)
    {
        $this->identity = $identity;
    }

    /**
     * @return mixed
     */
    public function getIdentity()
    {
        return $this->identity;
    }

    /**
     * @param mixed $ssid
     */
    public function setSsid($ssid)
    {
        $this->ssid = $ssid;
    }

    /**
     * @return mixed
     */
    public function getSsid()
    {
        return $this->ssid;
    }


}
