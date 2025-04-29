<?php

namespace Wideti\DomainBundle\Service\Campaign;

use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\Campaign\Selectors\CampaignSelector;
use Wideti\FrontendBundle\Factory\Nas;

interface CampaignSelectorService
{
    /**
     * @param Nas $nas
     * @param Client $client
     * @param $type
     * @return Campaign
     */
    public function select(Nas $nas = null, Client $client, $type = CampaignSelector::PRE_LOGIN);

    /**
     * @param CampaignSelector $campaignSelector
     * @return void
     */
    public function registerSelector(CampaignSelector $campaignSelector);
}
