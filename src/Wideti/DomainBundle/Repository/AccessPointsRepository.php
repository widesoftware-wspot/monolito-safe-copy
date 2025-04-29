<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Client;

/**
 * Class AccessPointsRepository
 * @package Wideti\DomainBundle\Repository
 */
class AccessPointsRepository extends EntityRepository
{
    /**
     * @param $client
     * @param null $filter
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function count($client, $filter = null)
    {
        $qb = $this->createQueryBuilder('a')
            ->select('count(a.id)')
            ->innerJoin('a.client', 'c', 'WITH', 'c.id = :client')
            ->setParameter('client', $client);

        if (isset($filter['value'])) {
            $qb
                ->andWhere('a.friendlyName LIKE :value')
                ->orWhere('a.identifier LIKE :value')
                ->orWhere('a.local LIKE :value')
                ->setParameter('value', "%{$filter['value']}%");
        }

        if ($filter['status'] !== 'all') {
            $qb
                ->andWhere('a.status = :status')
                ->setParameter('status', $filter['status']);
        }

        $query = $qb->getQuery();

        return $query->getSingleScalarResult();
    }

    /**
     * @param $client
     * @param null $maxResults
     * @param null $offset
     * @param null $filter
     * @return array
     */
    public function listAll($client, $maxResults = null, $offset = null, $filter = null)
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a')
            ->innerJoin('a.client', 'c', 'WITH', 'c.id = :client')
            ->orderBy('a.friendlyName', 'ASC')
            ->setParameter('client', $client);

        if (isset($filter['value'])) {
            $qb
                ->andWhere('a.friendlyName LIKE :value')
                ->orWhere('a.identifier LIKE :value')
                ->orWhere('a.local LIKE :value')
                ->setParameter('value', "%{$filter['value']}%");
        }

        if ($filter['status'] !== 'all') {
            $qb
                ->andWhere('a.status = :status')
                ->setParameter('status', $filter['status']);
        }

        if ($maxResults) {
            $qb->setMaxResults($maxResults);
        }

        if ($offset) {
            $qb->setFirstResult($offset);
        }

        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * @param $client
     * @return array
     */
    public function getRegisteredAps($client)
    {
        $qb  =  $this->createQueryBuilder('a')
            ->select('a.friendlyName')
            ->where('a.client = :client')
            ->setParameter('client', $client);

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @param $identifier
     * @param $client
     * @param int $apStatus
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getAccessPoint($identifier, $client, $apStatus = 1)
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.identifier = :identifier')
            ->innerJoin('c.client', 'i', 'WITH', 'c.client = :client')
            ->setParameter('client', $client)
            ->setParameter('identifier', $identifier);

        if ($apStatus) {
            $qb
                ->andWhere('c.status = :status')
                ->setParameter('status', $apStatus);
        }

        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * @param $identifier
     * @param $client
     * @param int $apStatus
     * @return array
     */
    public function getAccessPointByIdentifier($identifier, $client, $apStatus = AccessPoints::ACTIVE)
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.identifier = :identifier')
            ->andWhere('c.status = :status')
            ->innerJoin('c.client', 'i', 'WITH', 'c.client = :client')
            ->setParameter('client', $client)
            ->setParameter('identifier', $identifier)
            ->setParameter('status', $apStatus);

        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * @param $client
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getAccessPointsList($client)
    {
        $query = "
                SELECT f.id, f.friendly_name, f.identifier, f.status, f.public_ip, f.location
                FROM (
                    SELECT a.id, a.friendly_name AS friendly_name, a.identifier AS identifier, a.location AS location, a.status AS status, a.public_ip
                    FROM access_points a
                    WHERE a.client_id = :client
                ) as f
                GROUP BY f.id
                ";

        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->prepare($query);
        $statement->bindParam('client', $client, \PDO::PARAM_INT);
        $statement->execute();
        $result = $statement->fetchAll();

        return $result;
    }

    /**
     * @param $accessPoint
     * @return array
     */
    public function getManyAccessPointById($accessPoint)
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a.friendlyName as friendlyName, a.identifier as macAddress')
            ->where('a.id in (:accessPoint)')
            ->setParameter('accessPoint', $accessPoint);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $accessPoint
     * @return array
     */
    public function getAccessPointsById($accessPoint)
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.id in (:accessPoint)')
            ->setParameter('accessPoint', $accessPoint);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $options
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function queryApsToAccessCode($options)
    {
        $client         = $options['attr']['client'];
        $inAccessPoints = $options['attr']['inAccessPoints'];

        $qb = $this->createQueryBuilder('a')
            ->innerJoin('a.client', 'c', 'WITH', 'c.id = :client')
            ->setParameter('client', $client)
            ->orderBy('a.friendlyName', 'ASC')
        ;

        if ($inAccessPoints) {
            $qb
                ->where("a.id IN (:accessPoints)")
                ->setParameter('accessPoints', $this->findAccessCodeAps($client))
            ;
        }

        return $qb;
    }

    /**
     * @param $client
     * @return array
     */
    public function findAccessCodeAps($client)
    {
        $qb = $this->createQueryBuilder('ap')
            ->select('ap.id')
            ->innerJoin('ap.accessCode', 'ac')
            ->where('ap.client = :client')
            ->setParameter('client', $client);

        $results = $qb->getQuery()->getArrayResult();

        $aps = [];

        foreach ($results as $result) {
            array_push($aps, $result['id']);
        }

        return $aps;
    }

    /**
     * @param $client
     * @return array
     */
    public function findApsToAccessCode($client)
    {
        return $this->queryApsToAccessCode($client)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $client
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getAccessPointsListToGuestReport($client)
    {
        $query = "
                SELECT a.id, a.friendly_name AS friendly_name, a.identifier AS identifier
                FROM access_points a
                WHERE a.client_id = :client
                ";

        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->prepare($query);
        $statement->bindParam('client', $client, \PDO::PARAM_INT);
        $statement->execute();
        $results = $statement->fetchAll();

        $aps = [];
        foreach ($results as $result) {
            if ($result['identifier']) {
                $aps[$result['identifier']] = $result['friendly_name'];
            }
        }

        return $aps;
    }

    /**
     * @param $field
     * @param Client $client
     * @return boolean
     */
    public function exists($field, $value, Client $client)
    {
        $result = $this->createQueryBuilder('ap')
            ->select('ap.id')
            ->where("ap.{$field} = :value")
            ->setParameter('value', $value)
            ->andWhere('ap.client = :client')
            ->setParameter('client', $client)
            ->getQuery()
            ->execute();
        return !empty($result);
    }

    /**
     * @param $client
     * @return array
     */
    public function getAccessPointWithoutGroup($client)
    {
        $qb = $this->createQueryBuilder('ap')
            ->where('ap.group IS NULL')
            ->andWhere('ap.client = :client')
            ->setParameter('client', $client);

        return $qb->getQuery()->getResult();
    }


    /**
     * @return int
     */
    public function countAllAccessPoints()
    {
        $query = $this->createQueryBuilder('ap')
            ->select('ap.id')
            ->getQuery();

        return count($query->getResult());
    }

    /**
     * @return int
     */
    public function countAllActiveAccessPoints()
    {
        $query = $this->createQueryBuilder('ap')
            ->select('ap.id')
            ->where('ap.status = :status')
            ->setParameter('status', AccessPoints::ACTIVE)
            ->getQuery();

        return count($query->getResult());
    }

    /**
     * @return int
     */
    public function countAllInactiveAccessPoints()
    {
        $query = $this->createQueryBuilder('ap')
            ->select('ap.id')
            ->where('ap.status = :status')
            ->setParameter('status', AccessPoints::INACTIVE)
            ->getQuery();

        return count($query->getResult());
    }

    /**
     * @return \Doctrine\ORM\Query
     */
    public function accessPointsPerDomains()
    {
        $query = "SELECT COUNT(ap.client) as totalAps, c.company, c.domain
                      FROM DomainBundle:AccessPoints ap
                      INNER JOIN DomainBundle:Client c WITH ap.client = c.id
                      WHERE ap.status = 1
                     GROUP BY c.domain
                     ORDER BY totalAps DESC";


        return $this->getEntityManager()->createQuery($query);
    }

    /**
     * @return array
     */
    public function accessPointsPerDomainsResult()
    {
        $query = "SELECT COUNT(ap.client) as totalAps, c.company, c.domain
                      FROM DomainBundle:AccessPoints ap
                      INNER JOIN DomainBundle:Client c WITH ap.client = c.id
                      WHERE ap.status = 1
                     GROUP BY c.domain
                     ORDER BY totalAps DESC";


        return $this->getEntityManager()->createQuery($query)->getResult();
    }

    /**
     * @return int
     */
    public function countAccessPointsPOC()
    {
        $query = "SELECT ap.id
                    FROM DomainBundle:AccessPoints ap
                    INNER JOIN DomainBundle:Client c WITH ap.client = c.id
                    WHERE c.status = 2";

        return count($this->getEntityManager()->createQuery($query)->getResult());
    }

    /**
     * @param Client $client
     * @throws \Doctrine\DBAL\DBALException
     */
    public function clearByClient(Client $client)
    {
        $clientId = $client->getId();

        $query  = "SET SQL_SAFE_UPDATES = 0;";
        $query .= "DELETE FROM access_points WHERE client_id = {$clientId};";

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);

        $statement->execute();
    }

    /**
     * @param $accessPoint
     * @return array
     */
    public function getAccessPointGroup($accessPoint)
    {
        $query = "SELECT group_id FROM access_points WHERE id = {$accessPoint}";
        return $this->getEntityManager()->createQuery($query)->getResult();
    }

    /**
     * @param AccessPoints $accessPoint
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(AccessPoints $accessPoint) {
        $em = $this->getEntityManager();
        $em->persist($accessPoint);
        $em->flush();
    }

    /**
     * @return array
     */
    public function getApVendorListFromClient(Client $client)
    {
        $query = $this->createQueryBuilder('ap')
            ->select('ap.vendor')
            ->where('ap.client = :client')
            ->setParameter('client', $client)
            ->getQuery();

        return $query->getResult();
    }

    /**
     * @param $identifier
     * @param $clientId
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getAPController($identifier, $clientId)
    {
        $query = "
            SELECT
                concat(ctrl.address, ':', ctrl.port) AS address
                
            FROM
                access_points ap
                INNER JOIN clients_controllers_unifi ccu
                    ON ap.client_id = ccu.client_id
                INNER JOIN controllers_unifi ctrl
                    ON ccu.unifi_id = ctrl.id

            WHERE
                ap.client_id = :clientId
                AND ap.identifier = :identifier
            LIMIT 1;
        ";

        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->prepare($query);
        $statement->bindParam('clientId', $clientId, \PDO::PARAM_INT);
        $statement->bindParam('identifier', $identifier, \PDO::PARAM_STR);
        $statement->execute();
        $result = $statement->fetchAll();

        if (count($result) > 0) {
            $result = $result[0]['address'];
        }
        else {
            $result = false;
        }
        return $result;
    }
}
