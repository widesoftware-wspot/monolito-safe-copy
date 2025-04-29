<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\AccessCodeControl;

class AccessCodeControlRepository extends EntityRepository
{

    /**
     * @param $guestId
     * @return AccessCodeControl|null
     */
    public function findByGuestId($guestId)
    {
        return $this->findOneBy([
            'guestId' => $guestId
        ]);
    }
}