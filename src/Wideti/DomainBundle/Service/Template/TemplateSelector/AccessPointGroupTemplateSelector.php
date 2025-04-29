<?php

namespace Wideti\DomainBundle\Service\Template\TemplateSelector;

use Doctrine\ORM\EntityManager;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Template;
use Wideti\DomainBundle\Service\AccessPointsGroups\AccessPointsGroupsService;
use Wideti\FrontendBundle\Factory\Nas;

class AccessPointGroupTemplateSelector implements TemplateSelector
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var AccessPointsGroups
     */
    private $accessPointsGroups;

    public function __construct(EntityManager $entityManager, AccessPointsGroupsService $accessPointsGroups)
    {
        $this->entityManager = $entityManager;
        $this->accessPointsGroups = $accessPointsGroups;
    }

    /**
     * @param Nas|null $nas
     * @param Client $client
     * @param Campaign|null $campaign
     * @return null|Template
     * @throws \Exception
     */
    public function select(Nas $nas = null, Client $client, Campaign $campaign = null)
    {
        if (!$nas) {
            return null;
        }

        /** @var AccessPoints $accessPoint */
        $accessPoint = $this->entityManager
            ->getRepository("DomainBundle:AccessPoints")
            ->getAccessPoint($nas->getAccessPointMacAddress(), $client);

        if (!$accessPoint || !$accessPoint->getGroup()) {
            return null;
        }

        return $this->accessPointsGroups->getParentTemplateByAccessPointsGroup($accessPoint->getGroup());
    }
}
