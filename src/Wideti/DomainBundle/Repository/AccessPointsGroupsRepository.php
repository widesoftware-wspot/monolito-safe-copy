<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\Pagination;

class AccessPointsGroupsRepository extends EntityRepository
{
    private $groupCount = 0;

    public function hasDefaultGroup($client)
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a')
            ->where('a.client = :client')
            ->andWhere('a.groupName LIKE :value')
            ->andWhere('a.isDefault = :isDefault')
            ->setParameter('client', $client)
            ->setParameter('value', "%Grupo padrão%")
            ->setParameter('isDefault', true);

        $query = $qb->getQuery();

        if (!$query->getResult()) {
            return false;
        }

        return $query->getSingleResult();
    }

    public function exists($id, Client $client)
    {
        $qb = $this->createQueryBuilder('apg')
            ->select('apg.id')
            ->where('apg.client = :client')
            ->andWhere('apg.id = :id')
            ->setParameter('client', $client)
            ->setParameter('id', $id);

        $result = $qb
            ->getQuery()
            ->execute();

        return !empty($result);
    }

    public function clearByClient(Client $client)
    {
        $clientId = $client->getId();

        $query  = "SET SQL_SAFE_UPDATES = 0;";
        $query .= "UPDATE
                   access_points ap
                   INNER JOIN access_points_groups apg ON ap.group_id = apg.id
                   SET ap.group_id = NULL
                   WHERE apg.is_default = 0
                   AND apg.client_id = {$clientId};";
        $query .= "DELETE FROM access_points_groups WHERE client_id = {$clientId} AND is_default = 0;";

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);

        $statement->execute();
    }

    public function findChilds($parent_id, Client $client)
    {
        $qb = $this->createQueryBuilder("a")
            ->select()
            ->where("a.client = :client")
            ->andWhere("a.parent = :parent")
            ->setParameter("client", $client)
            ->setParameter("parent", $parent_id)
        ;
        return $qb->getQuery()->getResult();
    }

	/**
	 * @param $groupId
	 * @param Client $client
	 * @return object|AccessPointsGroups|null
	 */
    public function getGroupById($groupId, Client $client)
    {
        return $this->getEntityManager()
            ->getRepository("DomainBundle:AccessPointsGroups")
            ->findOneBy([
            	"client" => $client,
            	"id" => $groupId
            ]);
    }

    /**
     * @param $groupId
     * @return int
     */
    public function getGroupHierarchyCount($groupId)
    {
        $em = $this->getEntityManager();

        $groups = $em->getRepository("DomainBundle:AccessPointsGroups")
            ->findBy(["parent" => $groupId]);

        if ($groups) {
            $this->countGroups($em, $groups);
        }

        return $this->groupCount;
    }

    /**
     * @param EntityManager $em
     * @param AccessPointsGroupsRepository $groups
     * @return int
     */
    public function countGroups(EntityManager $em, array $groups)
    {
        foreach ($groups as $group) {
            $this->groupCount++;

            $subGroups = $em->getRepository("DomainBundle:AccessPointsGroups")
                ->findBy([ "parent" => $group->getId() ]);

            if ($subGroups) {
                $this->countGroups($em, $subGroups);
            }
        }

        return $this->groupCount;
    }

    /**
     * @param Client $client
     * @param Pagination $pagination
     * @param $pagination_array
     * @return array|AccessPointsGroups[]
     */
    public function getEntitiesByClient(Client $client, Pagination $pagination, $pagination_array)
    {
        return  $this->getEntityManager()
            ->getRepository('DomainBundle:AccessPointsGroups')
            ->findBy(['client' => $client],
                ["isDefault" => "DESC"],
                $pagination->getPerPage(),
                $pagination_array['offset']
            );
    }

    /**
     * @param $groupId
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setIsMaster($groupId) {
        $em = $this->getEntityManager();

        $group = $em->getRepository("DomainBundle:AccessPointsGroups")
            ->findOneBy(["id" => $groupId]);

        if ($group) {
            $group->setIsMaster(true)
                ->setParentConfigurations(false)
                ->setParentTemplate(false)
                ->setParent(0);

            $em->persist($group);
            $em->flush();
        }
    }

}