<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;

class ZoneRepository extends EntityRepository
{
    /**
     * @return array
     */
    public function queryAllTimezonesExceptBrazilian()
    {
        $qb = $this->createQueryBuilder('z');
        $qb->where('z.countryCode != :brazil')
            ->setParameter('brazil', 'BR')
            ->orderBy('z.zoneName', 'ASC');

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @return array
     */
    public function queryAllBrazilianTimezones()
    {
        $qb = $this->createQueryBuilder('z');
        $qb->where('z.countryCode = :brazil')
            ->setParameter('brazil', 'BR')
            ->orderBy('z.zoneName', 'ASC');

        return $qb->getQuery()
            ->getResult();
    }
}
