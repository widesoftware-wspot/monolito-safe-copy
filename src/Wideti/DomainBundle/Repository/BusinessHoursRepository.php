<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\BusinessHours;
use Wideti\DomainBundle\Entity\Client;

class BusinessHoursRepository extends EntityRepository
{
	public function getAll(Client $client)
	{
		$qb = $this->createQueryBuilder('b');
		$qb = $qb->select()
			->leftJoin('b.item', 'item')
			->leftJoin('b.accessPoints', 'accessPoints')
			->where('b.client = :client')
			->setParameter('client', $client)
			->orderBy('b.created', 'ASC')
		;

		$statement = $qb->getQuery();

		return $statement->getResult();
	}

	public function getById($id)
	{
		$qb = $this->createQueryBuilder('b');
		$qb = $qb->select()
			->leftJoin('b.item', 'item')
			->leftJoin('b.accessPoints', 'accessPoints')
			->where('b.id = :id')
			->setParameter('id', $id)
		;

		$statement = $qb->getQuery();

		return $statement->getSingleResult();
	}

	public function delete(BusinessHours $businessHours)
	{
		$businessHoursId = $businessHours->getId();

		$query  = "DELETE FROM business_hours_access_points WHERE business_hours_id = {$businessHoursId};";
		$query .= "DELETE FROM business_hours_item WHERE business_hours_id = {$businessHoursId};";
		$query .= "DELETE FROM business_hours WHERE id = {$businessHoursId};";
		$connection = $this->getEntityManager()->getConnection();
		$statement  = $connection->prepare($query);
		$statement->execute();
	}

	public function removeRelationships($businessHoursId)
	{
		$query  = "DELETE FROM business_hours_access_points WHERE business_hours_id = {$businessHoursId};";
		$query .= "DELETE FROM business_hours_item WHERE business_hours_id = {$businessHoursId};";
		$connection = $this->getEntityManager()->getConnection();
		$statement  = $connection->prepare($query);
		$statement->execute();
	}

	public function getByAccessPoint($accessPointId)
	{
		$qb = $this->createQueryBuilder('b');
		$qb = $qb->select()
			->leftJoin('b.item', 'item')
			->leftJoin('b.accessPoints', 'accessPoints')
			->where('accessPoints.id = :apId')
			->setParameter('apId', $accessPointId)
		;

		$statement  = $qb->getQuery();
		$result     = $statement->getResult();
		return $result ? $result[0] : null;
	}

	public function getByAllAccessPoints(Client $client)
	{
		$qb = $this->createQueryBuilder('b');
		$qb = $qb->select()
			->leftJoin('b.item', 'item')
			->leftJoin('b.accessPoints', 'accessPoints')
			->where('b.client = :client')
			->andWhere('b.inAccessPoints = 0')
			->setParameter('client', $client)
		;

		$statement  = $qb->getQuery();
		$result     = $statement->getResult();
		return $result ? $result[0] : null;
	}

	public function getByAccessPointAndBusinessHoursId(Client $client, $accessPoint, $id)
	{
		$clientId = $client->getId();

		if ($accessPoint) {
			$query = "
				SELECT *
				FROM business_hours b
				INNER JOIN business_hours_access_points ap ON ap.business_hours_id = b.id
				WHERE b.client_id = {$clientId}
				AND b.id <> {$id}
				AND ap.access_point_id = {$accessPoint};
			";
		} else {
			$query = "
				SELECT *
				FROM business_hours
				WHERE client_id = {$clientId}
				AND id <> {$id}
				AND in_access_points = 0;
			";
		}

		$connection = $this->getEntityManager()->getConnection();
		$statement  = $connection->prepare($query);
		$statement->execute();
		$result     = $statement->fetchAll();

		return $result ? $result[0] : null;
	}
}
