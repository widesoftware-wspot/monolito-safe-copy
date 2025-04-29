<?php

namespace Wideti\DomainBundle\Service\NasManager\Dto\RadiusPolicy;

final class AccessPointPolicy implements \JsonSerializable
{
    private $calledStationName;
    private $calledStationId;
    private $callingStationId;
    private $vendorName;
	private $routerMode;
    private $timezone;

	/**
	 * AccessPointPolicy constructor.
	 * @param string $calledStationName
	 * @param string $calledStationId
	 * @param string $callingStationId
	 * @param string $vendorName
	 * @param $routerMode
	 * @param string $timezone
	 */
    public function __construct($calledStationName, $calledStationId, $callingStationId, $vendorName, $routerMode, $timezone)
    {
        $this->calledStationName = $calledStationName;
        $this->calledStationId = $calledStationId;
        $this->callingStationId = $callingStationId;
        $this->vendorName = $vendorName;
	    $this->routerMode = $routerMode;
	    $this->timezone = $timezone;
    }

    /**
     * @return string
     */
    public function getCalledStationName()
    {
        return $this->calledStationName;
    }

    /**
     * @return string
     */
    public function getCalledStationId()
    {
        return $this->calledStationId;
    }

    /**
     * @return string
     */
    public function getCallingStationId()
    {
        return $this->callingStationId;
    }

    /**
     * @return string
     */
    public function getVendorName()
    {
        return $this->vendorName;
    }

	/**
	 * @return mixed
	 */
	public function getRouterMode()
	{
		return $this->routerMode;
	}

	/**
	 * @param mixed $routerMode
	 */
	public function setRouterMode($routerMode)
	{
		$this->routerMode = $routerMode;
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
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
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
        $params = get_object_vars($this);
        $result = [];
        foreach ($params as $param => $value) {
            $result[$param] = $value;
        }
        return $result;
    }
}
