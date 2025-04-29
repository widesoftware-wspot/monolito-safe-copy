<?php


namespace Wideti\DomainBundle\Service\CampaignViews\Dto;

class AggregatedViewsDto implements \JsonSerializable
{
    private $clientId;
    private $campaignId;
    private $step;
    private $lastAggregatedTime;
    private $total;

    /**
     * AggregatedViewsDto constructor.
     * @param $clientId
     * @param $campaignId
     * @param $step
     * @param $lastAggregatedTime
     * @param $total
     */
    public function __construct($clientId, $campaignId, $step, $lastAggregatedTime, $total)
    {
        $this->clientId = $clientId;
        $this->campaignId = $campaignId;
        $this->step = $step;
        $this->lastAggregatedTime = $lastAggregatedTime;
        $this->total = $total;
    }

    /**
     * @return mixed
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param mixed $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @return mixed
     */
    public function getCampaignId()
    {
        return $this->campaignId;
    }

    /**
     * @param mixed $campaignId
     */
    public function setCampaignId($campaignId)
    {
        $this->campaignId = $campaignId;
    }

    /**
     * @return mixed
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * @param mixed $step
     */
    public function setStep($step)
    {
        $this->step = $step;
    }

    /**
     * @return mixed
     */
    public function getLastAggregatedTime()
    {
        return $this->lastAggregatedTime;
    }

    /**
     * @param mixed $lastAggregatedTime
     */
    public function setLastAggregatedTime($lastAggregatedTime)
    {
        $this->lastAggregatedTime = $lastAggregatedTime;
    }

    /**
     * @return mixed
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param mixed $total
     */
    public function setTotal($total)
    {
        $this->total = $total;
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
            'clientId'              => $this->clientId,
            'campaignId'            => $this->campaignId,
            'step'                  => $this->step,
            'lastAggregatedTime'    => $this->lastAggregatedTime,
            'total'                 => $this->total
        ];
    }
}
