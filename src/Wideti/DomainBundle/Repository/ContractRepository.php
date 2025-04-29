<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class ContractRepository
 * @package Wideti\DomainBundle\Repository
 */
class ContractRepository extends EntityRepository
{
    /**
     * @param $type
     * @return mixed
     */
    public function getContractByType($type)
    {
        return $this->getEntityManager()
            ->getRepository("DomainBundle:Contract")
            ->findOneByType($type);
    }
}