<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;

class ModuleRepository extends EntityRepository
{
    public function checkClientModule($shortCode = null, $client = null)
    {
        $qb = $this->createQueryBuilder('m')
            ->select('m')
            ->innerJoin('m.client', 'c')
            ->where('m.shortCode = :shortCode')
            ->andWhere('c.id = :client')
            ->setParameter('shortCode', $shortCode)
            ->setParameter('client', $client);

        $result =  $qb->getQuery();

        return $result->getResult();
    }

    public function getDefaultModules($modulesDefault = [])
    {
        return $this->createQueryBuilder('m')
            ->select('m')
            ->where('m.shortCode IN (:shortCodeList)')
            ->setParameter(':shortCodeList', $modulesDefault)
            ->getQuery()
            ->getResult();
    }

    public function findClientModule( $client = null)
    {
        $qb = $this->createQueryBuilder('m')
            ->select('m')
            ->innerJoin('m.client', 'c')
            ->where('c.id = :client')
            ->setParameter('client', $client);

        $result =  $qb->getQuery();

        return $result->getResult();
    }

}
