<?php

namespace Wideti\DomainBundle\Twig;

use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class CampaignExhibitionType extends \Twig_Extension
{
    use EntityManagerAware;
    use SessionAware;

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('campaign_exhibition_type', [$this, 'getCampaignExhibitionType'])
        ];
    }

    public function getCampaignExhibitionType($campaignId)
    {
        $preLogin = $this->em
            ->getRepository('DomainBundle:Campaign')
            ->checkIfHasPreAndPos($campaignId, 'pre');

        $posLogin = $this->em
            ->getRepository('DomainBundle:Campaign')
            ->checkIfHasPreAndPos($campaignId, 'pos');

        if ($preLogin && $posLogin) {
            return "Pré e Pós Login";
        }

        if ($preLogin && !$posLogin) {
            return "Pré Login";
        }

        if (!$preLogin && $posLogin) {
            return "Pós Login";
        }

        return 'Não informado';
    }

    public function getName()
    {
        return 'campaign_exhibition_type';
    }
}
