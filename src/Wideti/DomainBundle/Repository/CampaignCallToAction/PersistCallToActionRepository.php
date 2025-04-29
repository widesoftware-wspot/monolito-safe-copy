<?php

namespace Wideti\DomainBundle\Repository\CampaignCallToAction;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\CampaignCallToAction;

/**
 * Class CreateRepository
 * @package Wideti\DomainBundle\Repository\CampaignCallToAction
 */
class PersistCallToActionRepository extends EntityRepository
{
    /**
     * @param CampaignCallToAction $callToAction
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function persist(CampaignCallToAction $callToAction)
    {
        $em = $this->getEntityManager();
        $em->persist($callToAction);
        $em->flush();
    }
}