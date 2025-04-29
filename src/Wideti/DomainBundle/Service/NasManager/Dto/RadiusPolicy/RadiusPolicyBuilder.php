<?php

namespace Wideti\DomainBundle\Service\NasManager\Dto\RadiusPolicy;

use Wideti\DomainBundle\Helpers\GeneratePolicyIdHelper;

final class RadiusPolicyBuilder
{
    const DEFAULT_INT_VALUE     = 0;
    const DEFAULT_STRING_VALUE  = '';
    const DEFAULT_BOOLEAN_VALUE = false;

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

    private function __construct()
    {
        $this->client = new ClientPolicy(
        	self::DEFAULT_INT_VALUE,
	        self::DEFAULT_STRING_VALUE,
	        self::DEFAULT_BOOLEAN_VALUE
        );
        $this->guest = new GuestPolicy(
            self::DEFAULT_INT_VALUE,
            self::DEFAULT_STRING_VALUE,
            self::DEFAULT_BOOLEAN_VALUE

        );
        $this->accessPoint = new AccessPointPolicy(
            self::DEFAULT_STRING_VALUE,
            self::DEFAULT_STRING_VALUE,
            self::DEFAULT_STRING_VALUE,
            self::DEFAULT_STRING_VALUE,
	        self::DEFAULT_STRING_VALUE,
            self::DEFAULT_STRING_VALUE
        );
        $this->bandwidth = new BandwidthPolicy(
            self::DEFAULT_INT_VALUE,
            self::DEFAULT_INT_VALUE,
            self::DEFAULT_BOOLEAN_VALUE
        );
        $this->timeLimit = new TimeLimitPolicy(
            self::DEFAULT_STRING_VALUE,
            self::DEFAULT_INT_VALUE,
            self::DEFAULT_BOOLEAN_VALUE
        );

        $date = new \DateTimeImmutable();
        $this->created = $date->format('Y-m-d H:i:s');
        $this->id = GeneratePolicyIdHelper::generate();
    }

    static function create()
    {
        return new RadiusPolicyBuilder();
    }

	/**
	 * @param int $id
	 * @param string $plan
	 * @param boolean $apCheck
	 * @return $this
	 */
    public function withClientPolicy($id, $plan, $apCheck)
    {
        $this->client = new ClientPolicy($id, $plan, $apCheck);
        return $this;
    }

    /**
     * @param int $username
     * @param string $password
     * @param $employee
     * @return $this
     */
    public function withGuestPolicy($username, $password, $employee = false)
    {
        $this->guest = new GuestPolicy($username, $password, $employee);
        return $this;
    }

	/**
	 * @param $calledStationName
	 * @param $calledStationId
	 * @param $callingStationId
	 * @param $vendorName
	 * @param $routerMode
	 * @param $timezone
	 * @return $this
	 */
    public function withAccessPointPolicy(
    	$calledStationName,
	    $calledStationId,
	    $callingStationId,
	    $vendorName,
	    $routerMode,
	    $timezone
    ) {
        $this->accessPoint = new AccessPointPolicy(
        	$calledStationName,
	        $calledStationId,
	        $callingStationId,
	        $vendorName,
	        $routerMode,
	        $timezone
        );
        return $this;
    }

    /**
     * @param int $download
     * @param int $upload
     * @param int $hasLimit
     * @return $this
     */
    public function withBandwidthPolicy($download, $upload, $hasLimit)
    {
        $this->bandwidth = new BandwidthPolicy($download, $upload, $hasLimit);
        return $this;
    }

    /**
     * @param string $module
     * @param int $time
     * @param boolean $hasTime
     * @return $this
     */
    public function withTimeLimitPolicy($module, $time, $hasTime)
    {
        $this->timeLimit = new TimeLimitPolicy($module, $time, $hasTime);
        return $this;
    }

    /**
     * @param $id
     * @return $this
     */
    public function withId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param \DateTimeImmutable $date
     * @return $this
     */
    public function createdIn($date)
    {
        $this->created = $date->format('Y-m-d H:i:s');
        return $this;
    }

    public function build()
    {
        return new RadiusPolicy(
            $this->client,
            $this->guest,
            $this->accessPoint,
            $this->bandwidth,
            $this->timeLimit,
            $this->created,
            $this->id
        );
    }
}
