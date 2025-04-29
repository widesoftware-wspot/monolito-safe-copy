<?php

namespace Wideti\DomainBundle\Service\Campaign\Selectors;

use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Entity\Client;
use Wideti\FrontendBundle\Factory\Nas;

interface CampaignSelector
{
    const PRE_LOGIN = 'pre';
    const POS_LOGIN = 'pos';

    /**
     * @param Nas $nas
     * @param Client $client
     * @param string $type
     * @return Campaign
     */
    public function select(Nas $nas = null, Client $client, $type);
}
