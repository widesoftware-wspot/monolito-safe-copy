<?php


namespace Wideti\DomainBundle\Service\CampaignViews\Builder;

use Wideti\DomainBundle\Service\CampaignViews\Dto\AggregatedViewsDto;

class AggregatedViewsBuilder
{
    private $clientId;
    private $campaignId;
    private $step;
    private $lastAggregatedTime;
    private $total;

    /**
     * @return AggregatedViewsBuilder
     */
    public static function getBuilder()
    {
        return new AggregatedViewsBuilder();
    }

    /**
     * @param $clientId
     * @return $this
     */
    public function withClientId($clientId)
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * @param $campaignId
     * @return $this
     */
    public function withCampaignId($campaignId)
    {
        $this->campaignId = $campaignId;
        return $this;
    }

    /**
     * @param $step
     * @return $this
     */
    public function withStep($step)
    {
        $this->step = $step;
        return $this;
    }

    /**
     * @param $lastAggregatedTime
     * @return $this
     */
    public function withLastAggregatedTime($lastAggregatedTime)
    {
        $this->lastAggregatedTime = $lastAggregatedTime;
        return $this;
    }

    /**
     * @param $total
     * @return $this
     */
    public function withTotal($total)
    {
        $this->total = $total;
        return $this;
    }

    /**
     * @return AggregatedViewsDto
     */
    public function build()
    {
        return new AggregatedViewsDto(
            $this->clientId,
            $this->campaignId,
            $this->step,
            $this->lastAggregatedTime,
            $this->total
        );
    }
}
