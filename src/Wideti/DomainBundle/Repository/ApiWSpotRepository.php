<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Wideti\DomainBundle\Entity\Client;

class ApiWSpotRepository extends EntityRepository
{
    public function getAllByClient(Client $client)
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.client = :client')
            ->setParameter('client', $client);

        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result;
    }

    public function getByResourceName(Client $client, $resourceName)
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.client = :client')
            ->innerJoin('a.resources', 'r')
            ->andWhere('r.resource = :resource')
            ->setParameter('client', $client)
            ->setParameter('resource', $resourceName)
        ;

        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result ? $result[0] : null;
    }

    public function refreshUser(UserInterface $user)
    {
        return $user;
    }

    public function supportsClass($class)
    {
        return $this->getEntityName() === $class || is_subclass_of($class, $this->getEntityName());
    }
}
