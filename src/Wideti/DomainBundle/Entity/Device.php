<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Device
 * @ORM\Table("devices")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\DeviceRepository")
 * @package Wideti\DomainBundle\Entity
 */
class Device
{
    const UNKNOWN = 'unknown';

    /**
     * @ORM\Id()
     * @ORM\Column(name="mac_address", type="string")
     */
    private $macAddress;

    /**
     * @ORM\Column(name="os", type="string")
     */
    private $os;

    /**
     * @ORM\Column(name="platform", type="string")
     */
    private $platform;

    /**
     * @var \DateTime
     * @ORM\Column(name="created", type="datetime", length=25)
     */
    private $created;

    /**
     * Device constructor.
     * @param $macAddress
     * @param $os
     * @param $platform
     * @throws \Exception
     */
    public function __construct($macAddress, $os, $platform)
    {
        $this->macAddress = $macAddress;
        $this->os = $os;
        $this->platform = $platform;
        $this->created = (new \DateTime())->setTimezone(new \DateTimeZone("UTC"));
    }

    /**
     * @return mixed
     */
    public function getMacAddress()
    {
        return $this->macAddress;
    }

    /**
     * @return mixed
     */
    public function getOs()
    {
        return $this->os;
    }

    /**
     * @return mixed
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }
}
