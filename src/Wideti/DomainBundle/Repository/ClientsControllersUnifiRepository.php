<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\ClientsControllersUnifi;

/**
 * Class ClientsControllersUnifiRepository
 * @package Wideti\DomainBundle\Repository
 */
class ClientsControllersUnifiRepository extends EntityRepository
{
    /**
     * @param $unifiId
     * @param $clientId
     * @return array
     */
    public function getClientsControllersByUnique($unifiId, $clientId)
    {
        $qb = $this->createQueryBuilder('ccu')
            ->where('ccu.unifiId = :unifiId')
            ->andWhere('ccu.clientId = :clientId')
            ->setParameter('unifiId', $unifiId)
            ->setParameter('clientId', $clientId);

        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * @param $clientId
     * @return array
     */
    public function getClientsControllersByClient($clientId)
    {
        $queryBuilder = $this->createQueryBuilder('ccu')
            ->where('ccu.clientId = :client_id' )
            ->setParameter('client_id', $clientId);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param $clientId
     * @return array
     */
    public function deleteByClientId($clientId)
    {
        $delete = $this->createQueryBuilder("ccu")
            ->delete()
            ->where("ccu.clientId = :client_id")
            ->setParameter("client_id", $clientId)
        ;
        $delete->getQuery()->execute();
    }
}