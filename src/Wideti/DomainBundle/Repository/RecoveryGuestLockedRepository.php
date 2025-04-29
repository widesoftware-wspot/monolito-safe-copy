<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\RecoveryGuestLocked;

class RecoveryGuestLockedRepository extends EntityRepository
{
    public function save(RecoveryGuestLocked $locked)
    {
        $this->_em->persist($locked);
        $this->_em->flush();
    }

    public function remove(RecoveryGuestLocked $locked)
    {
        $this->_em->remove($locked);
        $this->_em->flush();
    }

    public function getRecoveryLockedByGuestId($guestMySqlId)
    {
        return $this->findOneBy(['guestId' => $guestMySqlId]);
    }
}