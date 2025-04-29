<?php

namespace Wideti\DomainBundle\Service\Campaign;

use Wideti\DomainBundle\Service\Campaign\CampaignService;

/**
 *
 * Usage: - [ setCampaignService, ["@core.service.campaign"] ]
 */
trait CampaignAware
{
    /**
     * @var campaignService
     */
    protected $campaignService;

    public function setCampaignService(CampaignService $service)
    {
        $this->campaignService = $service;
    }
}
