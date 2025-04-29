<?php


namespace Wideti\DomainBundle\Repository;


use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\SmartLocation;


class SmartLocationRepository extends EntityRepository
{
    public function save(SmartLocation $martLocationCredentials)
    {
        $this->_em->merge($martLocationCredentials);
        $this->_em->flush();
    
        return $martLocationCredentials;
    }
}