<?php

namespace Wideti\DomainBundle\Service\Template\TemplateSelector;

use Doctrine\ORM\EntityManager;
use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Template;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\FrontendBundle\Factory\Nas;

class CampaignTemplateSelector implements TemplateSelector
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
     * @param Campaign $campaign
     * @return Template
     */
    public function select(Nas $nas = null, Client $client, Campaign $campaign = null)
    {
        /**
         * @var Template $template
         */
        $template = null;

        if (!$campaign) {
            return null;
        }

        return $campaign->getTemplate();
    }
}
