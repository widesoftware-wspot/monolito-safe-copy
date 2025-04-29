<?php

namespace Wideti\DomainBundle\Service\Campaign\Selectors;

use Doctrine\ORM\EntityManager;
use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Entity\Client;
use Wideti\FrontendBundle\Factory\Nas;

class AccessPointGroupCampaignSelector implements CampaignSelector
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
        $accessPoint = $this->entityManager->getRepository('DomainBundle:AccessPoints')
            ->findOneBy([
                'identifier' => $nas->getAccessPointMacAddress(),
                'client'     => $client
            ]);

        if (!$accessPoint) {
            return null;
        }

        return $this->entityManager
            ->getRepository("DomainBundle:Campaign")
            ->getCampaignByAccessPointGroup($accessPoint->getGroup(), $client);
    }
}