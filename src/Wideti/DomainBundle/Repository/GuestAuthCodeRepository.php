<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;

class GuestAuthCodeRepository extends EntityRepository
{
    public function deleteByGuest($id)
    {
        $delete = $this->createQueryBuilder("a")
            ->delete()
            ->where("a.guest = :guest")
            ->setParameter("guest", $id)
        ;
        $delete->getQuery()->execute();
    }
}
