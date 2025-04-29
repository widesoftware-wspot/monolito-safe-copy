<?php

namespace Wideti\DomainBundle\Service\Campaign;

use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\Campaign\Selectors\CampaignSelector;
use Wideti\FrontendBundle\Factory\Nas;

class CampaignSelectorServiceImp implements CampaignSelectorService
{
    /**
     * @var CampaignSelector[]
     */
    private $selectors;

    public function __construct()
    {
        $this->selectors = [];
    }

    /**
     * @param Nas $nas
     * @param Client $client
     * @param string $type
     * @return Campaign
     */
    public function select(Nas $nas = null, Client $client, $type = CampaignSelector::PRE_LOGIN)
    {
        $campaign = null;

        foreach ($this->selectors as $selector) {
            $campaign = $selector->select($nas, $client, $type);
            if ($campaign) {
                break;
            }
        }

        return $campaign;
    }

    public function registerSelector(CampaignSelector $campaignSelector)
    {
        $this->selectors[] = $campaignSelector;
    }
}
