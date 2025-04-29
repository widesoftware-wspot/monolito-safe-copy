<?php

namespace Wideti\DomainBundle\Dto;

/**
 * Class AccessPointsDto
 * @package Wideti\DomainBundle\Dto
 */
class AccessPointsDto
{
    /**
     * @var string
     */
    private $identifier;
    /**
     * @var string
     */
    private $friendlyName;
    /**
     * @var string
     */
    private $downloadField;
    /**
     * @var string
     */
    private $uploadField;

    /**
     * AccessPointsDto constructor.
     * @param string $identifier
     * @param string $friendlyName
     * @param string $downloadField
     * @param string $uploadField
     */
    public function __construct($identifier, $friendlyName, $downloadField, $uploadField)
    {
        $this->identifier    = $identifier;
        $this->friendlyName  = $friendlyName;
        $this->downloadField = $downloadField;
        $this->uploadField   = $uploadField;
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
     * @return AccessPointsDto
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
     * @return AccessPointsDto
     */
    public function setFriendlyName($friendlyName)
    {
        $this->friendlyName = $friendlyName;
        return $this;
    }

    /**
     * @return string
     */
    public function getDownloadField()
    {
        return $this->downloadField;
    }

    /**
     * @param string $downloadField
     * @return AccessPointsDto
     */
    public function setDownloadField($downloadField)
    {
        $this->downloadField = $downloadField;
        return $this;
    }

    /**
     * @return string
     */
    public function getUploadField()
    {
        return $this->uploadField;
    }

    /**
     * @param string $uploadField
     * @return AccessPointsDto
     */
    public function setUploadField($uploadField)
    {
        $this->uploadField = $uploadField;
        return $this;
    }
}