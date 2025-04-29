<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\Users;

/**
 * Class ContractUserRepository
 * @package Wideti\DomainBundle\Repository
 */
class ContractUserRepository extends EntityRepository
{
    /**
     * @param $user
     * @return mixed
     */
    public function getByUser($user)
    {
        return $this->getEntityManager()
            ->getRepository("DomainBundle:ContractUser")
            ->findOneByUser($user);
    }
}