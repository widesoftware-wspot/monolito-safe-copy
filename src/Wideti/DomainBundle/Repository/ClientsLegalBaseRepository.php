<?php


namespace Wideti\DomainBundle\Repository;


use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\ClientsLegalBase;

class ClientsLegalBaseRepository extends EntityRepository
{
    public function save(ClientsLegalBase $clientsLegalBase)
    {
        $this->getEntityManager()->merge($clientsLegalBase);
        $this->getEntityManager()->flush();
    }
}