<?php

namespace Wideti\DomainBundle\Service\NasManager\Dto\RadiusPolicy;

final class RadiusPolicy implements \JsonSerializable
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var ClientPolicy
     */
    private $client;
    /**
     * @var GuestPolicy
     */
    private $guest;
    /**
     * @var AccessPointPolicy
     */
    private $accessPoint;
    /**
     * @var BandwidthPolicy
     */
    private $bandwidth;
    /**
     * @var TimeLimitPolicy
     */
    private $timeLimit;
    /**
     * @var string
     */
    private $created;

    /**
     * RadiusPolicy constructor.
     * @param ClientPolicy $client
     * @param GuestPolicy $guest
     * @param AccessPointPolicy $accessPoint
     * @param BandwidthPolicy $bandwidth
     * @param TimeLimitPolicy $timeLimit
     * @param string $created
     */
    public function __construct(
        ClientPolicy $client,
        GuestPolicy $guest,
        AccessPointPolicy $accessPoint,
        BandwidthPolicy $bandwidth,
        TimeLimitPolicy $timeLimit,
        $created,
        $id
    ) {
        $this->client       = clone $client;
        $this->guest        = clone $guest;
        $this->accessPoint  = clone $accessPoint;
        $this->bandwidth    = clone $bandwidth;
        $this->timeLimit    = clone $timeLimit;
        $this->created      = $created;
        $this->id           = $id;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ClientPolicy
     */
    public function getClient()
    {
        return clone $this->client;
    }

    /**
     * @return GuestPolicy
     */
    public function getGuest()
    {
        return clone $this->guest;
    }

    /**
     * @return AccessPointPolicy
     */
    public function getAccessPoint()
    {
        return clone $this->accessPoint;
    }

    /**
     * @return BandwidthPolicy
     */
    public function getBandwidth()
    {
        return clone $this->bandwidth;
    }

    /**
     * @return TimeLimitPolicy
     */
    public function getTimeLimit()
    {
        return clone $this->timeLimit;
    }

    /**
     * @return string
     */
    public function getCreated()
    {
        return $this->created;
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
        $parameters = get_object_vars($this);
        $result = [];
        foreach ($parameters as $param => $value) {
            $result[$param] = $this->{$param};

        }
        return $result;
    }

    public function toArray($includeId = false)
    {
        $asArray = json_decode(json_encode($this), true);

        if ($includeId) {
            return $asArray;
        }
        unset($asArray['id']);
        return $asArray;
    }
}
