<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Entity\CampaignCallToAction;
use Wideti\DomainBundle\Entity\Client;

class CampaignRepository extends EntityRepository
{
    const FILTER_STATUS_ALL = "all";

    public function getCampaignBySsid($ssid, $client)
    {
        if (!$ssid || !$client) {
            return null;
        }

        $count = $this->createQueryBuilder('c')
            ->select('COUNT(c)')
            ->innerJoin('c.campaignHours', 'h')
            ->where('c.client = :client')
            ->andWhere('c.ssid = :ssid')
            ->andWhere('c.startDate <= :today')
            ->andWhere('c.endDate >= :today')
            ->andWhere('h.startTime <= :now')
            ->andWhere('h.endTime >= :now')
            ->andWhere('c.status = 1')
            ->setParameter('client', $client)
            ->setParameter('ssid', $ssid)
            ->setParameter('today', date('Y-m-d'))
            ->setParameter('now', date('H:i'));

        $count = $count
            ->getQuery()
            ->getSingleScalarResult();

        $qb  =  $this->createQueryBuilder('c')
            ->select('c', 'h')
            ->innerJoin('c.campaignHours', 'h')
            ->where('c.client = :client')
            ->andWhere('c.ssid = :ssid')
            ->andWhere('c.startDate <= :today')
            ->andWhere('c.endDate >= :today')
            ->andWhere('h.startTime <= :now')
            ->andWhere('h.endTime >= :now')
            ->andWhere('c.status = 1')
            ->setFirstResult(rand(0, $count - 1))
            ->setMaxResults(1)
            ->setParameter('client', $client)
            ->setParameter('ssid', $ssid)
            ->setParameter('today', date('Y-m-d'))
            ->setParameter('now', date('H:i'));

        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    public function getCampaignBySsidAndAccessPoints($ssid, $accessPoint, $client)
    {
        if (!$accessPoint || !$ssid || !$client) {
            return null;
        }

        $count = $this->createQueryBuilder('c')
            ->select('COUNT(c)')
            ->innerJoin('c.campaignHours', 'h')
            ->innerJoin('c.accessPoints', 'a')
            ->where('c.client = :client')
            ->andWhere('c.ssid = :ssid')
            ->andWhere('a.id = :accessPoint')
            ->andWhere('c.startDate <= :today')
            ->andWhere('c.endDate >= :today')
            ->andWhere('h.startTime <= :now')
            ->andWhere('h.endTime >= :now')
            ->andWhere('c.status = 1')
            ->andWhere('c.inAccessPoints = 1')
            ->setParameter('client', $client)
            ->setParameter('ssid', $ssid)
            ->setParameter('accessPoint', $accessPoint->getId())
            ->setParameter('today', date('Y-m-d'))
            ->setParameter('now', date('H:i'));

        $count = $count
            ->getQuery()
            ->getSingleScalarResult();

        $qb  =  $this->createQueryBuilder('c')
            ->select('c', 'h')
            ->innerJoin('c.campaignHours', 'h')
            ->innerJoin('c.accessPoints', 'a')
            ->where('c.client = :client')
            ->andWhere('c.ssid = :ssid')
            ->andWhere('a.id = :accessPoint')
            ->andWhere('c.startDate <= :today')
            ->andWhere('c.endDate >= :today')
            ->andWhere('h.startTime <= :now')
            ->andWhere('h.endTime >= :now')
            ->andWhere('c.status = 1')
            ->andWhere('c.inAccessPoints = 1')
            ->setFirstResult(rand(0, $count - 1))
            ->setMaxResults(1)
            ->setParameter('client', $client)
            ->setParameter('ssid', $ssid)
            ->setParameter('accessPoint', $accessPoint->getId())
            ->setParameter('today', date('Y-m-d'))
            ->setParameter('now', date('H:i'));

        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    public function getCampaignByAllAccessPoint($client)
    {
        if (!$client) {
            return null;
        }

        $count = $this->createQueryBuilder('c')
            ->select('COUNT(c)')
            ->innerJoin('c.campaignHours', 'h')
            ->where('c.client = :client')
            ->andWhere('c.startDate <= :today')
            ->andWhere('c.endDate >= :today')
            ->andWhere('h.startTime <= :now')
            ->andWhere('h.endTime >= :now')
            ->andWhere('c.status = 1')
            ->andWhere('c.inAccessPoints = 0')
            ->setParameter('client', $client)
            ->setParameter('today', date('Y-m-d'))
            ->setParameter('now', date('H:i'));

        $count = $count
            ->getQuery()
            ->getSingleScalarResult();

        $qb  =  $this->createQueryBuilder('c')
            ->select('c', 'h')
            ->innerJoin('c.campaignHours', 'h')
            ->where('c.client = :client')
            ->andWhere('c.startDate <= :today')
            ->andWhere('c.endDate >= :today')
            ->andWhere('h.startTime <= :now')
            ->andWhere('h.endTime >= :now')
            ->andWhere('c.status = 1')
            ->andWhere('c.inAccessPoints = 0')
            ->setFirstResult(rand(0, $count - 1))
            ->setMaxResults(1)
            ->setParameter('client', $client)
            ->setParameter('today', date('Y-m-d'))
            ->setParameter('now', date('H:i'));

        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    public function getCampaignByAccessPoint($accessPoint, $client)
    {
        if (!$accessPoint || !$client) {
            return null;
        }

        $count = $this->createQueryBuilder('c')
            ->select('COUNT(c)')
            ->innerJoin('c.campaignHours', 'h')
            ->innerJoin('c.accessPoints', 'a')
            ->where('c.client = :client')
            ->andWhere('a.id = :accessPoint')
            ->andWhere('c.startDate <= :today')
            ->andWhere('c.endDate >= :today')
            ->andWhere('h.startTime <= :now')
            ->andWhere('h.endTime >= :now')
            ->andWhere('c.status = 1')
            ->setParameter('client', $client)
            ->setParameter('accessPoint', $accessPoint->getId())
            ->setParameter('today', date('Y-m-d'))
            ->setParameter('now', date('H:i'));

        $count = $count
            ->getQuery()
            ->getSingleScalarResult();

        $qb  =  $this->createQueryBuilder('c')
            ->select('c', 'h')
            ->innerJoin('c.campaignHours', 'h')
            ->innerJoin('c.accessPoints', 'a')
            ->where('c.client = :client')
            ->andWhere('a.id = :accessPoint')
            ->andWhere('c.startDate <= :today')
            ->andWhere('c.endDate >= :today')
            ->andWhere('h.startTime <= :now')
            ->andWhere('h.endTime >= :now')
            ->andWhere('c.status = 1')
            ->setFirstResult(rand(0, $count - 1))
            ->setMaxResults(1)
            ->setParameter('client', $client)
            ->setParameter('accessPoint', $accessPoint->getId())
            ->setParameter('today', date('Y-m-d'))
            ->setParameter('now', date('H:i'));

        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    public function getCampaignByAccessPointGroup(AccessPointsGroups $accessPointsGroups, Client $client)
    {
        if (!$accessPointsGroups || !$client) {
            return null;
        }

        $count = $this->createQueryBuilder('c')
            ->select('COUNT(c)')
            ->innerJoin('c.campaignHours', 'h')
            ->innerJoin('c.accessPointsGroups', 'g')
            ->where('c.client = :client')
            ->andWhere('g.id = :accessPointGroup')
            ->andWhere('c.startDate <= :today')
            ->andWhere('c.endDate >= :today')
            ->andWhere('h.startTime <= :now')
            ->andWhere('h.endTime >= :now')
            ->andWhere('c.status = 1')
            ->setParameter('client', $client)
            ->setParameter('accessPointGroup', $accessPointsGroups->getId())
            ->setParameter('today', date('Y-m-d'))
            ->setParameter('now', date('H:i'));

        $count = $count
            ->getQuery()
            ->getSingleScalarResult();

        $qb  =  $this->createQueryBuilder('c')
            ->select('c', 'h')
            ->innerJoin('c.campaignHours', 'h')
            ->innerJoin('c.accessPointsGroups', 'g')
            ->where('c.client = :client')
            ->andWhere('g.id = :accessPointGroup')
            ->andWhere('c.startDate <= :today')
            ->andWhere('c.endDate >= :today')
            ->andWhere('h.startTime <= :now')
            ->andWhere('h.endTime >= :now')
            ->andWhere('c.status = 1')
            ->setFirstResult(rand(0, $count - 1))
            ->setMaxResults(1)
            ->setParameter('client', $client)
            ->setParameter('accessPointGroup', $accessPointsGroups->getId())
            ->setParameter('today', date('Y-m-d'))
            ->setParameter('now', date('H:i'));

        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    public function getCampaignById($campaignId, $client)
    {
        $count = $this->createQueryBuilder('c')
            ->select('COUNT(c)')
            ->innerJoin('c.campaignHours', 'h')
            ->where('c.client = :client')
            ->andWhere('c.id = :campaignId')
            ->andWhere('c.startDate <= :today')
            ->andWhere('c.endDate >= :today')
            ->andWhere('h.startTime <= :now')
            ->andWhere('h.endTime >= :now')
            ->andWhere('c.status = 1')
            ->setParameter('client', $client)
            ->setParameter('campaignId', $campaignId)
            ->setParameter('today', date('Y-m-d'))
            ->setParameter('now', date('H:i'));

        $count = $count
            ->getQuery()
            ->getSingleScalarResult();

        $qb  =  $this->createQueryBuilder('c')
            ->select('c', 'h')
            ->innerJoin('c.campaignHours', 'h')
            ->where('c.client = :client')
            ->andWhere('c.id = :campaignId')
            ->andWhere('c.startDate <= :today')
            ->andWhere('c.endDate >= :today')
            ->andWhere('h.startTime <= :now')
            ->andWhere('h.endTime >= :now')
            ->andWhere('c.status = 1')
            ->setFirstResult(rand(0, $count - 1))
            ->setMaxResults(1)
            ->setParameter('client', $client)
            ->setParameter('campaignId', $campaignId)
            ->setParameter('today', date('Y-m-d'))
            ->setParameter('now', date('H:i'));

        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    public function getClientsThatHasNotMadeCampaign()
    {
        $query = "SELECT DISTINCT client_id FROM campaign";
        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->prepare($query);
        $statement->execute();
        $result = $statement->fetchAll();

        return $result;
    }

    public function deleteAllByClient(Client $client)
    {
        $clientId = $client->getId();

        $query  = "SET SQL_SAFE_UPDATES = 0;";
        $query .= "DELETE FROM campaign WHERE client_id = $clientId;";

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);

        return $statement->execute();
    }

    /**
     * @param $filters
     * @return array|mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCampaignByFilter($filters)
    {
        $qb = $this->createQueryBuilder("c")
            ->where("c.client = :client")
            ->setParameter("client", $filters["client"]);

        if ($filters["status"] !== self::FILTER_STATUS_ALL) {
            $qb->andWhere("c.status = :status")
               ->setParameter("status", $filters["status"]);
        }

        if (!empty($filters["name"])) {
            $qb->andWhere("c.name LIKE :name");
            $qb->setParameter("name", "%{$filters["name"]}%");
        }

        if (!empty($filters["start_date"])) {
            $qb->andWhere("c.start_date <= :start_date");
            $qb->setParameter("start_date", $filters["start_date"]);
        }

        if (!empty($filters["end_date"])) {
            $qb->andWhere("c.end_date >= :end_date");
            $qb->setParameter("end_date", $filters["end_date"]);
        }

        $result = $qb->getQuery()->execute();
        $entities = $result;

        if (!empty($filters["access_points"])) {
            $entities = [];

            foreach ($filters["access_points"] as $accessPointId) {
                foreach ($result as $campaign) {
                    if ($this->checkCampaignAndAccessPoint($campaign, $accessPointId)) {
                        $entities[$campaign->getId()] = $campaign;
                    }
                }

                @reset($result);
            }
        }

        return $entities;
    }

    /**
     * @param Campaign $campaign
     * @param $accessPointId
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function checkCampaignAccessPoint(Campaign $campaign, $accessPointId)
    {
        $connection = $this->getEntityManager()->getConnection();

        $statement  = $connection->prepare(
            "SELECT * FROM campaign_access_points WHERE campaign_id = {$campaign->getId()} " .
            "AND access_point_id = {$accessPointId}"
        );

        $statement->execute();

        return $statement->rowCount() > 0;
    }

    /**
     * @param Campaign $campaign
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function campaignForAllAccessPoints(Campaign $campaign)
    {
        $connection = $this->getEntityManager()->getConnection();

        $statement  = $connection->prepare(
            "SELECT * FROM campaign_access_points WHERE campaign_id = {$campaign->getId()}"
        );

        $statement->execute();

        return $statement->rowCount() == 0;
    }

    /**
     * @param Campaign $campaign
     * @param $accessPointId
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function checkCampaignAndAccessPoint(Campaign $campaign, $accessPointId)
    {
        return (($this->campaignForAllAccessPoints($campaign)) ||
            ($this->checkCampaignAccessPoint($campaign, $accessPointId)));
    }

    /**
     * @param $filter
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function campaignCountByFilter($filter)
    {
        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare("SELECT COUNT(*) total FROM campaign WHERE {$filter}");
        $statement->execute();
        $result = $statement->fetchAll();

        return $result[0]["total"];
    }

    public function checkIfHasPreAndPos($campaignId, $step)
    {
        $connection = $this->getEntityManager()->getConnection();

        $statement  = $connection->prepare("
            SELECT COUNT(1) as count
            FROM campaign c
            LEFT JOIN campaign_media_image as ci ON ci.campaign_id = c.id
            LEFT JOIN campaign_media_video as cv ON cv.campaign_id = c.id
            WHERE c.id = {$campaignId}
            AND (
                ci.step = '{$step}'
                OR cv.step = '{$step}'
            );
        ");

        $statement->execute();
        $result = $statement->fetch();
        return $result['count'] ? (boolean) $result['count'] : false;
    }
}