<?php

namespace Wideti\DomainBundle\Service\AccessPoints\Dto\Api;

use Wideti\DomainBundle\Entity\AccessPoints;

class AccessPointDto implements \JsonSerializable
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $friendlyName;
    /**
     * @var string
     */
    private $created;
    /**
     * @var string
     */
    private $updated;
    /**
     * @var string
     */
    private $vendor;
    /**
     * @var string
     */
    private $identifier;
    /**
     * @var string
     */
    private $local;
    /**
     * @var string
     */
    private $timezone;
    /**
     * @var bool
     */
    private $verified;
    /**
     * @var int
     */
    private $status;

    /**
     * @var AccessPointTemplateDto
     */
    private $template;

    /**
     * @var AccessPointGroupDto
     */
    private $group;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return AccessPointDto
     */
    public function setId($id)
    {
        $this->id = $id;
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
     * @return AccessPointDto
     */
    public function setFriendlyName($friendlyName)
    {
        $this->friendlyName = $friendlyName;
        return $this;
    }

    /**
     * @return string
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param string $created
     * @return AccessPointDto
     */
    public function setCreated($created)
    {
        $this->created = $created;
        return $this;
    }

    /**
     * @return string
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param string $updated
     * @return AccessPointDto
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
        return $this;
    }

    /**
     * @return string
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * @param string $vendor
     * @return AccessPointDto
     */
    public function setVendor($vendor)
    {
        $this->vendor = $vendor;
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
     * @return AccessPointDto
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return string
     */
    public function getLocal()
    {
        return $this->local;
    }

    /**
     * @param string $local
     * @return AccessPointDto
     */
    public function setLocal($local)
    {
        $this->local = $local;
        return $this;
    }

    /**
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @param string $timezone
     * @return AccessPointDto
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
        return $this;
    }

    /**
     * @return bool
     */
    public function isVerified()
    {
        return $this->verified;
    }

    /**
     * @param bool $verified
     * @return AccessPointDto
     */
    public function setVerified($verified)
    {
        $this->verified = $verified;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return AccessPointDto
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return AccessPointTemplateDto
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param AccessPointTemplateDto $template
     * @return AccessPointDto
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return AccessPointGroupDto
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param AccessPointGroupDto $group
     * @return AccessPointDto
     */
    public function setGroup($group)
    {
        $this->group = $group;
        return $this;
    }

    /**
     * @param AccessPoints[] $accessPoints
     * @return AccessPointDto[]
     */
    public static function createFromAccessPointArray(array $accessPoints)
    {
        $result = [];

        if (empty($accessPoints)) return $result;

        foreach ($accessPoints as $ap) {
            $result[] = self::createFromAccessPointEntity($ap);
        }

        return $result;
    }

    /**
     * @param AccessPoints $ap
     * @return AccessPointDto
     */
    public static function createFromAccessPointEntity(AccessPoints $ap = null)
    {
        if (!$ap) return null;

        $apiDto = new AccessPointDto();

        $apTemplate = $ap->getTemplate()
            ? new AccessPointTemplateDto($ap->getTemplate()->getId(), $ap->getTemplate()->getName())
            : new AccessPointTemplateDto(null, null);


        $created = $ap->getCreated()
            ? $ap->getCreated()->format('Y-m-d H:i:s')
            : '';

        $updated = $ap->getUpdated()
            ? $ap->getUpdated()->format('Y-m-d H:i:s')
            : '';

        return $apiDto
            ->setId($ap->getId())
            ->setFriendlyName($ap->getFriendlyName())
            ->setCreated($created)
            ->setUpdated($updated)
            ->setVendor($ap->getVendor())
            ->setIdentifier($ap->getIdentifier())
            ->setLocal($ap->getLocal())
            ->setTimezone($ap->getTimezone())
            ->setVerified($ap->isFullVerified())
            ->setStatus((int) $ap->getStatus())
            ->setTemplate($apTemplate)
            ->setGroup(new AccessPointGroupDto($ap->getGroup()->getId(), $ap->getGroup()->getGroupName()));
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
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
