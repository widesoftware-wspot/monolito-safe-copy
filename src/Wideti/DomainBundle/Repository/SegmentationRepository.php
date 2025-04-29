<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\Segmentation;

class SegmentationRepository extends EntityRepository
{
    /**
     * @param Segmentation $segmentation
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Segmentation $segmentation)
    {
        $em = $this->getEntityManager();
        $em->persist($segmentation);
        $em->flush();
    }

    public function delete(Segmentation $segmentation)
    {
        $em = $this->getEntityManager();
        $em->remove($segmentation);
        $em->flush();
    }
}
