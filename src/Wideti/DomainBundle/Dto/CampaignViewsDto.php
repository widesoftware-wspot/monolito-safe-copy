<?php

namespace Wideti\DomainBundle\Dto;

use Wideti\DomainBundle\Entity\Campaign;

class CampaignViewsDto implements \JsonSerializable
{
    private $campaign;
    private $type;
    private $guestId;
    private $guestMacAddress;
    private $accessPoint;

	/**
	 * @return mixed
	 */
	public function getGuestId()
	{
		return $this->guestId;
	}

	/**
	 * @param $guestId
	 * @return $this
	 */
	public function setGuestId($guestId)
	{
		$this->guestId = $guestId;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getGuestMacAddress()
	{
		return $this->guestMacAddress;
	}

	/**
	 * @param $guestMacAddress
	 * @return $this
	 */
	public function setGuestMacAddress($guestMacAddress)
	{
		$this->guestMacAddress = $guestMacAddress;
		return $this;
	}

    /**
     * @return mixed
     */
    public function getAccessPoint()
    {
        return $this->accessPoint;
    }

    /**
     * @param $accessPoint
     * @return $this
     */
    public function setAccessPoint($accessPoint)
    {
        $this->accessPoint = $accessPoint;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * @param $campaign
     * @return $this
     */
    public function setCampaign($campaign)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
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
        return [
            'campaign'          => $this->campaign,
            'type'              => $this->type,
            'guestId'           => $this->guestId,
            'guestMacAddress'   => $this->guestMacAddress,
            'accessPoint'       => $this->accessPoint
        ];
    }
}