<?php

namespace Wideti\FrontendBundle\Factory;

use JsonSerializable;
use Wideti\DomainBundle\Service\NasManager\Dto\RadiusPolicy\RadiusPolicy;
use Wideti\FrontendBundle\Factory\NasHandlers\Dto\NasFormPostParameter;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;

final class NasImp implements Nas, JsonSerializable, \Serializable
{
    private $accessPointMacAddress;
    private $guestDeviceMacAddress;
    private $vendorName;
    private $extraParams;
    private $vendorRawParameters;
    /**
     * @var RadiusPolicy
     */
    private $radiusPolicy;

    /**
     * @var NasFormPostParameter
     */
    private $nasFormPostParameter;
    private $authorizeErrorUrl;

    /**
     * NasImp constructor.
     * @param string $accessPointMacAddress
     * @param string $guestDeviceMacAddress
     * @param string $vendorName
     * @param NasFormPostParameter $nasFormPostParameter
     * @param array $extraParams
     * @param array $vendorRawParameters
     */
    public function __construct(
        $accessPointMacAddress,
        $guestDeviceMacAddress,
        $vendorName,
        NasFormPostParameter $nasFormPostParameter,
        array $extraParams,
        array $vendorRawParameters
    ) {
        $this->accessPointMacAddress    = $accessPointMacAddress;
        $this->guestDeviceMacAddress    = $guestDeviceMacAddress;
        $this->vendorName               = $vendorName;
        $this->nasFormPostParameter     = $nasFormPostParameter;
        $this->extraParams              = $extraParams ?: [];
        $this->vendorRawParameters      = $vendorRawParameters ?: [];
    }

    /**
     * @return string
     */
    public function getAccessPointMacAddress()
    {
        return $this->accessPointMacAddress;
    }

    /**
     * @return string
     */
    public function getGuestDeviceMacAddress()
    {
        return $this->guestDeviceMacAddress;
    }

    /**
     * @return string
     */
    public function getVendorName()
    {
        return $this->vendorName;
    }

    public function __set($key, $value)
    {
        $this->$key = $value;
    }

    /**
     * @param $key
     * @return string
     */
    public function getExtraParam($key)
    {
        return isset($this->extraParams[$key]) ? $this->extraParams[$key] : "";
    }

    /**
     * @return array
     */
    public function getExtraParameters()
    {
        return $this->extraParams;
    }

    /**
     * @return NasFormPostParameter
     */
    public function getNasFormPost()
    {
        return clone $this->nasFormPostParameter;
    }

    /**
     * @return array
     */
    public function getVendorRawParameters()
    {
        return $this->vendorRawParameters;
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

    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        $properties = [];
        $fields = get_class_vars(get_class($this));

        foreach ($fields as $key => $value) {
            $objPropertiesValues = get_object_vars($this);
            $properties[$key] = $objPropertiesValues[$key];
        }

        return serialize($properties);
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $data <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($data)
    {
        $rawArray = unserialize($data);

        foreach ($rawArray as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * @param RadiusPolicy $radiusPolicy
     * @return void
     */
    public function setRadiusPolicy(RadiusPolicy $radiusPolicy)
    {
        $this->radiusPolicy = $radiusPolicy;
    }

    /**
     * @return RadiusPolicy
     */
    public function getRadiusPolicy()
    {
        return $this->radiusPolicy;
    }

    /**
     * @return string
     */
    public function getAuthorizeErrorUrl()
    {
        return $this->controllerHelper->generateUrl('frontend_authorize_error_url');
    }
}
