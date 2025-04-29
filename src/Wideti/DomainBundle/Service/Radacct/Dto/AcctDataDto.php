<?php

namespace Wideti\DomainBundle\Service\Radacct\Dto;

class AcctDataDto implements \JsonSerializable
{
    /** @var string */
    private $id;
    /** @var int */
    private $guest;
    /** @var boolean */
    private $isEmployee;
    /** @var string */
    private $guestDevice;
    /** @var string */
    private $guestIp;
    /** @var string */
    private $nasIpAddress;
    /** @var string */
    private $identifier;
    /** @var string */
    private $friendlyName;
    /** @var string */
    private $start;
    /** @var string */
    private $stop;
    /** @var int */
    private $acctInputOctets;
    /** @var int */
    private $acctOutputOctets;
    /** @var int */
    private $download;
    /** @var int */
    private $upload;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return AcctDataDto
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getGuest()
    {
        return $this->guest;
    }

    /**
     * @param int $guest
     * @return AcctDataDto
     */
    public function setGuest($guest)
    {
        $this->guest = $guest;
        return $this;
    }

    /**
     * @return bool
     */
    public function isEmployee()
    {
        return $this->isEmployee;
    }

    /**
     * @param bool $isEmployee
     * @return AcctDataDto
     */
    public function setIsEmployee($isEmployee)
    {
        $this->isEmployee = $isEmployee;
        return $this;
    }

    /**
     * @return string
     */
    public function getGuestDevice()
    {
        return $this->guestDevice;
    }

    /**
     * @param string $guestDevice
     * @return AcctDataDto
     */
    public function setGuestDevice($guestDevice)
    {
        $this->guestDevice = $guestDevice;
        return $this;
    }

    /**
     * @return string
     */
    public function getGuestIp()
    {
        return $this->guestIp;
    }

    /**
     * @param string $guestIp
     * @return AcctDataDto
     */
    public function setGuestIp($guestIp)
    {
        $this->guestIp = $guestIp;
        return $this;
    }

    /**
     * @return string
     */
    public function getNasIpAddress()
    {
        return $this->nasIpAddress;
    }

    /**
     * @param string $nasIpAddress
     * @return AcctDataDto
     */
    public function setNasIpAddress($nasIpAddress)
    {
        $this->nasIpAddress = $nasIpAddress;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     * @return AcctDataDto
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return string
     */
    public function getFriendlyName()
    {
        return $this->friendlyName;
    }

    /**
     * @param string $friendlyName
     * @return AcctDataDto
     */
    public function setFriendlyName($friendlyName)
    {
        $this->friendlyName = $friendlyName;
        return $this;
    }

    /**
     * @return string
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param string $start
     * @return AcctDataDto
     */
    public function setStart($start)
    {
        $this->start = $start;
        return $this;
    }

    /**
     * @return string
     */
    public function getStop()
    {
        return $this->stop;
    }

    /**
     * @param string $stop
     * @return AcctDataDto
     */
    public function setStop($stop)
    {
        $this->stop = $stop;
        return $this;
    }

    /**
     * @return int
     */
    public function getAcctInputOctets()
    {
        return $this->acctInputOctets;
    }

    /**
     * @param int $acctInputOctets
     * @return AcctDataDto
     */
    public function setAcctInputOctets($acctInputOctets)
    {
        $this->acctInputOctets = $acctInputOctets;
        return $this;
    }

    /**
     * @return int
     */
    public function getAcctOutputOctets()
    {
        return $this->acctOutputOctets;
    }

    /**
     * @param int $acctOutputOctets
     * @return AcctDataDto
     */
    public function setAcctOutputOctets($acctOutputOctets)
    {
        $this->acctOutputOctets = $acctOutputOctets;
        return $this;
    }

    /**
     * @return int
     */
    public function getDownload()
    {
        return $this->download;
    }

    /**
     * @param int $download
     */
    public function setDownload($download)
    {
        $this->download = $download;
        return $this;
    }

    /**
     * @return int
     */
    public function getUpload()
    {
        return $this->upload;
    }

    /**
     * @param int $upload
     */
    public function setUpload($upload)
    {
        $this->upload = $upload;
        return $this;
    }



    public function jsonSerialize()
    {
        $properties = [];
        $fields = get_class_vars(get_class($this));

        foreach ($fields as $key => $value) {
            $objPropertiesValues = get_object_vars($this);
            $properties[$key] = $objPropertiesValues[$key];
        }

        return $properties;
    }
}
