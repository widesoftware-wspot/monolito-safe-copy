<?php

namespace Wideti\DomainBundle\Service\Campaign\Selectors;

use Doctrine\ORM\EntityManager;
use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Entity\Client;
use Wideti\FrontendBundle\Factory\Nas;

class SsidCampaignSelector implements CampaignSelector
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Nas $nas
     * @param Client $client
     * @param string $type
     * @return Campaign
     */
    public function select(Nas $nas = null, Client $client, $type)
    {
        $ssid = $nas->getExtraParam(Nas::EXTRA_PARAM_SSID);
        $campaign = null;

        if ($ssid) {
            $campaign = $this->entityManager->getRepository('DomainBundle:Campaign')
                ->getCampaignBySsid($ssid, $client);
        }

        return $campaign;
    }
}
