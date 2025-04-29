<?php

namespace Wideti\DomainBundle\Service\RedirectUrl;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\Campaign\CampaignSelectorService;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\FrontendBundle\Factory\Nas;

class RedirectUrlServiceImp implements RedirectUrlService
{
    /**
     * @var CampaignSelectorService
     */
    private $campaignSelectorService;

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    public function __construct(
        CampaignSelectorService $campaignSelectorService,
        ConfigurationService $configurationService
    ) {
        $this->campaignSelectorService = $campaignSelectorService;
        $this->configurationService = $configurationService;
    }

    /**
     * @param Nas $nas
     * @param Client $client
     * @return string
     */
    public function getRedirectUrl(Nas $nas = null, Client $client)
    {
        if (!$nas) {
            return $this->configurationService->get($nas, $client, 'redirect_url');
        }

        $campaign = $this->campaignSelectorService->select($nas, $client);
        if ($campaign && $campaign->getRedirectUrl()) {
            return $campaign->getRedirectUrl();
        }

        return $this->configurationService->get($nas, $client, 'redirect_url');
    }
}