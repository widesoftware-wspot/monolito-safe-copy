<?php

namespace Wideti\DomainBundle\Service\NasManager\Dto\AuthReport;

use Wideti\DomainBundle\Entity\AccessPoints;

class AccessPointEvent implements \JsonSerializable
{
    private $id;
    private $identifier;
    private $vendor;

    private function __construct() {}

    public static function createFrom(AccessPoints $accessPoint)
    {
        $event = new AccessPointEvent();
        $event->setId($accessPoint->getId());
        $event->setIdentifier($accessPoint->getIdentifier());
        $event->setVendor($accessPoint->getVendor());

        return $event;
    }

    public static function createEmpty()
    {
        return new AccessPointEvent();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return AccessPointEvent
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param mixed $identifier
     * @return AccessPointEvent
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * @param mixed $vendor
     * @return AccessPointEvent
     */
    public function setVendor($vendor)
    {
        $this->vendor = $vendor;
        return $this;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource.
     *
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'identifier' => $this->identifier,
            'vendor' => $this->vendor
        ];
    }
}
