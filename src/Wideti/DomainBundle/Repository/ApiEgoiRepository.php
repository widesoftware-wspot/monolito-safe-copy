<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\Client;

class ApiEgoiRepository extends EntityRepository
{
	public function getByAllAccessPoints(Client $client)
	{
		$qb = $this->createQueryBuilder('a')
			->where('a.client = :client')
			->andWhere('a.inAccessPoints = 0')
			->setParameter('client', $client)
			->getQuery();

		$result = $qb->getResult();

		return $result ? $result[0] : null;
	}

	public function getByAccessPoint(Client $client, $accessPoint)
	{
		$accessPointId = $accessPoint->getId();

		$qb = $this->createQueryBuilder('a')
			->where('a.client = :client')
			->innerJoin('a.accessPoints', 'ap')
			->andWhere('ap.id = :apId')
			->setParameter('client', $client)
			->setParameter('apId', $accessPointId)
			->getQuery()
		;

		$result = $qb->getResult();

		return $result ? $result[0] : null;
	}

	public function getByAccessPointAndIntegrationId(Client $client, $accessPoint, $entityId)
	{
		if (!$entityId) return null;

		$clientId = $client->getId();

		$query = "
			SELECT a.*
			FROM api_egoi a
			INNER JOIN api_egoi_access_points ap ON ap.api_egoi_id = a.id
			WHERE a.client_id = {$clientId}
			AND a.in_access_points = 0
			AND a.id <> {$entityId}
			LIMIT 1;
		";

		if ($accessPoint) {
			$query = "
				SELECT a.*
				FROM api_egoi a
				INNER JOIN api_egoi_access_points ap ON ap.api_egoi_id = a.id
				WHERE a.client_id = {$clientId}
				AND ap.access_point_id IN ({$accessPoint})
				AND a.id <> {$entityId}
				LIMIT 1;
			";
		}

		$connection = $this->getEntityManager()->getConnection();
		$statement  = $connection->prepare($query);
		$statement->execute();
		$result     = $statement->fetchAll();

		return $result ? $result[0] : null;
	}
}
