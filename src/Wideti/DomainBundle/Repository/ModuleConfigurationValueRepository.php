<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;

class ModuleConfigurationValueRepository extends EntityRepository
{
    public function findByModuleConfigurationKey($client, $key)
    {
        $qb = $this->createQueryBuilder('mv')
            ->where('mv.client = :client')
            ->andWhere('mc.key = :key')
            ->innerJoin('mv.items', 'mc')
            ->setParameter('client', $client)
            ->setParameter('key', $key);

        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }
}
