<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Wideti\DomainBundle\Helpers\DateTimeHelper;

/**
 * Class DeviceAccess
 * @ORM\Table(
 *     name="devices_entries",
 *     uniqueConstraints={
 *          @UniqueConstraint(
 *              name="unique_references_ids",
 *              columns={
 *                  "mac_address",
 *                  "guest_id",
 *                  "client_id"
 *              }
 *          )
 *     }
 * )
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\DeviceEntryRepository")
 * @package Wideti\DomainBundle\Entity
 */
class DeviceEntry implements \JsonSerializable
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Wideti\DomainBundle\Entity\Device", cascade={"persist"})
     * @ORM\JoinColumn(name="mac_address", referencedColumnName="mac_address")
     */
    private $device;

    /**
     * @ORM\ManyToOne(targetEntity="Wideti\DomainBundle\Entity\Guests", cascade={"persist"})
     * @ORM\JoinColumn(name="guest_id", referencedColumnName="id")
     */
    private $guest;

    /**
     * @ORM\ManyToOne(targetEntity="Wideti\DomainBundle\Entity\Client", cascade={"persist"})
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     */
    private $client;

    /**
     * @var \DateTime
     * @ORM\Column(name="created", type="datetime", length=25)
     */
    private $created;

    /**
     * @var \DateTime
     * @ORM\Column(name="last_access", type="datetime", length=25)
     */
    private $lastAccess;

    /**
     * @ORM\Column(name="last_ap_identifier", type="string", length=255, nullable=true)
     */
    private $lastApIdentifier;

    /**
     * @ORM\Column(name="last_ap_friendly_name", type="string", length=255, nullable=true)
     */
    private $lastApFriendlyName;

    /**
     * @ORM\Column(name="timezone", type="string", length=55, nullable=true)
     */
    private $timezone;

    /**
     * @ORM\Column(name="has_chance_password", type="boolean", nullable=true, options={"default" : false})
     */
    private $hasChangePassword;

    /**
     * DeviceEntry constructor.
     * @param $device
     * @param $guest
     * @param $client
     * @param $lastApIdentifier
     * @param $lastApFriendlyName
     * @param $timezone
     */
    public function __construct($device, $guest, $client, $lastApIdentifier, $lastApFriendlyName, $timezone)
    {
        $this->device               = $device;
        $this->guest                = $guest;
        $this->client               = $client;
        $this->created              = (new \DateTime())->setTimezone(new \DateTimeZone("UTC"));
        $this->lastAccess           = (new \DateTime())->setTimezone(new \DateTimeZone("UTC"));
        $this->lastApIdentifier     = $lastApIdentifier;
        $this->lastApFriendlyName   = $lastApFriendlyName;
        $this->timezone             = $timezone;
    }

    public function updateLastAccessToNow(AccessPoints $accessPoint = null)
    {
        if (!is_null($accessPoint)) {
            $this->lastApIdentifier     = $accessPoint->getIdentifier();
            $this->lastApFriendlyName   = $accessPoint->getFriendlyName();
            $this->timezone             = $accessPoint->getTimezone();
        }
        $this->lastAccess           = (new \DateTime())->setTimezone(new \DateTimeZone("UTC"));
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * @return mixed
     */
    public function getGuest()
    {
        return $this->guest;
    }

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return \DateTime
     * @throws \Exception
     */
    public function getCreated()
    {
        return new \DateTime(
            $this->created->format("Y-m-d H:i:s.u"),
            new \DateTimeZone("UTC")
        );
    }

    /**
     * @return \DateTime
     * @throws \Exception
     */
    public function getLastAccess()
    {
        return new \DateTime(
            $this->lastAccess->format("Y-m-d H:i:s.u"),
            new \DateTimeZone("UTC")
        );
    }

    /**
     * @return mixed
     */
    public function getLastApIdentifier()
    {
        return $this->lastApIdentifier;
    }

    /**
     * @return mixed
     */
    public function getLastApFriendlyName()
    {
        return $this->lastApFriendlyName;
    }

    /**
     * @return mixed
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'client_id'     => $this->getClient()->getId(),
            'guest_id'      => $this->getGuest()->getId(),
            'mac_address'   => $this->getDevice()->getMacAddress(),
            'os'            => $this->getDevice()->getOs(),
            'platform'      => $this->getDevice()->getPlatform(),
            'created'       => DateTimeHelper::convertDateTimeToUnixTimestamp($this->getCreated()),
            'lastAccess'    => DateTimeHelper::convertDateTimeToUnixTimestamp($this->getLastAccess())
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function jsonSerialize_V1()
    {
        return [
            'os'            => $this->getDevice()->getOs(),
            'platform'      => $this->getDevice()->getPlatform(),
            'macaddress'    => $this->getDevice()->getMacAddress(),
            'accessDate'    => json_decode(json_encode($this->getCreated()), true)
        ];
    }

        /**
     *
     * @param bool $hasChangePassword
     */
    public function setHasChangePassword($hasChangePassword)
    {
        $this->hasChangePassword = $hasChangePassword;
    }

    /**
     *
     * @return bool
     */
    public function getHasChangePassword()
    {
        return $this->hasChangePassword;
    }
}
