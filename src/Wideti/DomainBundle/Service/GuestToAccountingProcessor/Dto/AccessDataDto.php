<?php

namespace Wideti\DomainBundle\Service\GuestToAccountingProcessor\Dto;

class AccessDataDto
{
    private $os;
    private $platform;
    private $macAddress;
    private $accessDate;

    /**
     * @return mixed
     */
    public function getOs()
    {
        return $this->os;
    }

    /**
     * @param mixed $os
     */
    public function setOs($os)
    {
        $this->os = $os;
    }

    /**
     * @return mixed
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @param mixed $platform
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
    }

    /**
     * @return mixed
     */
    public function getMacAddress()
    {
        return $this->macAddress;
    }

    /**
     * @param mixed $macAddress
     */
    public function setMacAddress($macAddress)
    {
        $this->macAddress = $macAddress;
    }

    /**
     * @return mixed
     */
    public function getAccessDate()
    {
        return $this->accessDate;
    }

    /**
     * @param mixed $accessDate
     */
    public function setAccessDate($accessDate)
    {
        $this->accessDate = $accessDate;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $vars = get_object_vars($this);
        $result = [];
        foreach ($vars as $key => $value) {
            $result[$key] = $value;
        }
        return $result;
    }
}
