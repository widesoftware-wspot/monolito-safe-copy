<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\Client;

class CustomFieldsTemplateRepository extends EntityRepository
{
	public function getAllFieldsAvailableToClient(Client $client)
	{
    $qb = $this->createQueryBuilder('c')
        ->select('c')
        ->where('c.visibleForClients like :domain')
        ->setParameter("domain", "%{$client->getDomain()}%")
        ->andWhere('c.identifier != :ageRestriction')
        ->setParameter("ageRestriction", 'age_restriction')
        ->orWhere('c.visibleForClients is null')
        ->orWhere("c.visibleForClients = ''")
    ;

		$query = $qb->getQuery();

		return $query->getResult();
	}
}
