<?php

namespace Wideti\DomainBundle\Repository\CampaignCallToAction;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\CallToActionAccessData;
use Wideti\DomainBundle\Entity\Campaign;

/**
 * Class AccessDataRepository
 * @package Wideti\DomainBundle\Repository\CampaignCallToAction
 */
class AccessDataRepository extends EntityRepository
{
	/**
	 * @param $campaignId
	 * @return null|object|Campaign
	 */
	public function countByCampaignId($campaignId)
	{
		$search = $this
			->getEntityManager()
			->getRepository('DomainBundle:CallToActionAccessData')
			->findBy([ "campaign" => $campaignId ]);

		if ($search) return count($search);
		return null;
	}

	/**
	 * @param $campaignId
	 * @return null|object|Campaign
	 */
	public function getCampaignById($campaignId)
	{
		return $this
			->getEntityManager()
			->getRepository('DomainBundle:Campaign')
			->findOneBy([ "id" => $campaignId ]);
	}

    /**
     * @param CallToActionAccessData $callToActionAccessData
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(CallToActionAccessData $callToActionAccessData)
    {
        $em = $this->getEntityManager();
        $em->persist($callToActionAccessData);
        $em->flush();
    }

	/**
	 * @param $guestMacAddress
	 * @param $campaignId
	 * @return null|object|CallToActionAccessData
	 */
    public function getGuestEqualZero($guestMacAddress, $campaignId)
    {
    	return $this
		    ->getEntityManager()
		    ->getRepository('DomainBundle:CallToActionAccessData')
		    ->findOneBy([
			    'type'       => 1,
			    'macAddress' => $guestMacAddress,
			    'guestId'    => 0
		    ]);
    }

	public function getCampaignsWithMoreClicks(
		$client         = null,
		$campaign       = null,
		$access_point   = null,
		$date_from      = null,
		$date_to        = null
	) {
		if ($access_point) {
			$query = "
                SELECT id, campanha, quantidade
                FROM (
                    SELECT DISTINCT(c.id) AS id, c.name AS campanha, count(*) AS quantidade
                    FROM campaign c
                    INNER JOIN call_to_action_access_data cta
                    ON c.id = cta.campaign_id
                    LEFT JOIN campaign_access_points p
                    ON c.id = p.campaign_id
                    WHERE c.client_id = :client
                ";

			$accessPointString = join(',', $access_point);
			$query .= "AND ( 
                        p.access_point_id IN ({$accessPointString})
                        OR c.in_access_points = 0
                    )";

			if ($campaign) {
				$campaign = join(',', $campaign);
				$query .= "AND cta.campaign_id IN (".$campaign.")";
			}

			if ($date_from) {
				$query .= "AND cta.view_date >= '".date_format($date_from, 'Y-m-d 00:00:00')."'";
			}

			if ($date_to) {
				$query .= "AND cta.view_date <= '".date_format($date_to, 'Y-m-d 23:59:59')."'";
			}

			$query .= "
                    GROUP BY cta.campaign_id, p.access_point_id
                ) AS x
                GROUP BY campanha
                LIMIT 10
                ";
		} else {
			$query = "
                SELECT id, campanha, quantidade
                FROM (
                    SELECT c.id, c.name AS campanha, count(cta.id) AS quantidade
                    FROM call_to_action_access_data cta
                    INNER JOIN campaign c
                    ON cta.campaign_id = c.id
                    WHERE c.client_id = :client
                    AND cta.id is not null
                ";

			if ($campaign) {
				$campaign = join(',', $campaign);
				$query .= " AND cta.campaign_id IN (".$campaign.")";
			}

			if ($date_from) {
				$query .= " AND cta.view_date >= '".date_format($date_from, 'Y-m-d 00:00:00')."'";
			}

			if ($date_to) {
				$query .= " AND cta.view_date <= '".date_format($date_to, 'Y-m-d 23:59:59')."'";
			}

			$query .= "
                    GROUP BY cta.campaign_id
                    ORDER BY quantidade DESC
                ) AS x
                GROUP BY campanha
                ORDER BY quantidade DESC
                LIMIT 10
                ";
		}

		$connection = $this->getEntityManager()->getConnection();
		$statement = $connection->prepare($query);
		$statement->bindParam('client', $client, \PDO::PARAM_INT);

		$statement->execute();
		$result = $statement->fetchAll();

		return $result;
	}

	public function getMostClickedByCampaign(
		$campaign = null,
		$params = null,
		$type
	) {
		$query = "
			SELECT cta.type as type, count(*) as quantity
			FROM call_to_action_access_data cta
			WHERE cta.campaign_id = :campaign
			AND cta.type = :c_type ";

		if (array_key_exists('date_from', $params) && $params['date_from'] != '') {
			$query .= "AND cta.view_date >= '" .
				substr($params['date_from'], 6, 8) . "-" .
				substr($params['date_from'], 3, 2) . "-" .
				substr($params['date_from'], 0, 2) . " 00:00:00' ";
		}

		if (array_key_exists('date_to', $params) && $params['date_to'] != '') {
			$query .= "AND cta.view_date <= '" .
				substr($params['date_to'], 6, 8) . "-" .
				substr($params['date_to'], 3, 2) . "-" .
				substr($params['date_to'], 0, 2) . " 23:59:59' ";
		}

		$query .= "GROUP BY cta.type";

		$connection = $this->getEntityManager()->getConnection();
		$statement = $connection->prepare($query);
		$statement->bindParam('campaign', $campaign, \PDO::PARAM_INT);
		$statement->bindParam('c_type', $type, \PDO::PARAM_INT);
		$statement->execute();
		$result = $statement->fetchAll();

		return $result;
	}

	public function getMostClickedByGuest($params)
	{
		$campaignId = $params['campaignId'];
		$type       = $params['type'];

		$query = "
			SELECT cta.guest_id as guest, cta.type as type, cta.mac_address, cta.ap_mac_address, cta.url, cta.view_date
			FROM call_to_action_access_data cta
			WHERE cta.campaign_id = :campaign
			AND cta.type = :c_type ";

		if (array_key_exists('date_from', $params) && $params['date_from'] != '') {
			$query .= "AND cta.view_date >= '" .
				substr($params['date_from'], 6, 8) . "-" .
				substr($params['date_from'], 3, 2) . "-" .
				substr($params['date_from'], 0, 2) . " 00:00:00' ";
		}

		if (array_key_exists('date_to', $params) && $params['date_to'] != '') {
			$query .= "AND cta.view_date <= '" .
				substr($params['date_to'], 6, 8) . "-" .
				substr($params['date_to'], 3, 2) . "-" .
				substr($params['date_to'], 0, 2) . " 23:59:59' ";
		}

		$query .= "ORDER BY cta.view_date DESC LIMIT 10";

		$connection = $this->getEntityManager()->getConnection();
		$statement = $connection->prepare($query);
		$statement->bindParam('campaign', $campaignId, \PDO::PARAM_INT);
		$statement->bindParam('c_type', $type, \PDO::PARAM_INT);
		$statement->execute();
		$result = $statement->fetchAll();

		return $result;
	}

	public function getCampaignsWithMoreClicksByDayOfWeek(
		$client         = null,
		$campaign       = null,
		$access_point   = null,
		$date_from      = null,
		$date_to        = null
	) {
		if ($access_point) {
			$query = "
                SELECT view_date, quantity
                FROM (
                    SELECT DATE_FORMAT(cta.view_date, \"%Y-%m-%d\") AS view_date, count(*) AS quantity
					FROM campaign c
					INNER JOIN call_to_action_access_data cta
					ON c.id = cta.campaign_id
					LEFT JOIN campaign_access_points p
					ON c.id = p.campaign_id
                    WHERE c.client_id = :client
                ";

			$accessPointString = join(',', $access_point);
			$query .= "AND ( 
                        p.access_point_id IN ({$accessPointString})
                        OR c.in_access_points = 0
                    )";

			if ($campaign) {
				$campaign = join(',', $campaign);
				$query .= "AND cta.campaign_id IN (".$campaign.")";
			}

			if ($date_from) {
				$query .= "AND cta.view_date >= '".$date_from."'";
			}

			if ($date_to) {
				$query .= "AND cta.view_date <= '".$date_to."'";
			}

			$query .= "
                    GROUP BY DATE_FORMAT(cta.view_date, \"%y-%m-%d\")
                ) AS x
                ";
		} else {
			$query = "
                SELECT view_date, quantity
                FROM (
                    SELECT DATE_FORMAT(cta.view_date, \"%Y-%m-%d\") AS view_date, count(*) AS quantity
                    FROM call_to_action_access_data cta
                    INNER JOIN campaign c
                    ON cta.campaign_id = c.id
                    WHERE c.client_id = :client
                    AND cta.id is not null
                ";

			if ($campaign) {
				$campaign = join(',', $campaign);
				$query .= " AND cta.campaign_id IN (".$campaign.")";
			}

			if ($date_from) {
				$query .= " AND cta.view_date >= '".$date_from."'";
			}

			if ($date_to) {
				$query .= " AND cta.view_date <= '".$date_to."'";
			}

			$query .= "
                    GROUP BY DATE_FORMAT(cta.view_date, \"%y-%m-%d\")
                ) AS x
                ";
		}

		$connection = $this->getEntityManager()->getConnection();
		$statement = $connection->prepare($query);
		$statement->bindParam('client', $client, \PDO::PARAM_INT);

		$statement->execute();
		$result = $statement->fetchAll();

		return $result;
	}

	public function getCampaignsWithMoreClicksByHours(
		$client         = null,
		$campaign       = null,
		$access_point   = null,
		$date_from      = null,
		$date_to        = null
	) {
		if ($access_point) {
			$query = "
                SELECT hour, quantity
                FROM (
                    SELECT DATE_FORMAT(cta.view_date, \"%H\") AS hour, count(*) AS quantity
					FROM campaign c
					INNER JOIN call_to_action_access_data cta
					ON c.id = cta.campaign_id
					LEFT JOIN campaign_access_points p
					ON c.id = p.campaign_id
                    WHERE c.client_id = :client
                ";

			$accessPointString = join(',', $access_point);
			$query .= "AND ( 
                        p.access_point_id IN ({$accessPointString})
                        OR c.in_access_points = 0
                    )";

			if ($campaign) {
				$campaign = join(',', $campaign);
				$query .= "AND cta.campaign_id IN (".$campaign.")";
			}

			if ($date_from) {
				$query .= "AND cta.view_date >= '".$date_from."'";
			}

			if ($date_to) {
				$query .= "AND cta.view_date <= '".$date_to."'";
			}

			$query .= "
                    GROUP BY DATE_FORMAT(cta.view_date, \"%H\")
                ) AS x
                ORDER BY quantity DESC
            ";
		} else {
			$query = "
                SELECT hour, quantity
                FROM (
                    SELECT DATE_FORMAT(cta.view_date, \"%H\") AS hour, count(*) AS quantity
                    FROM call_to_action_access_data cta
                    INNER JOIN campaign c
                    ON cta.campaign_id = c.id
                    WHERE c.client_id = :client
                    AND cta.id is not null
                ";

			if ($campaign) {
				$campaign = join(',', $campaign);
				$query .= " AND cta.campaign_id IN (".$campaign.")";
			}

			if ($date_from) {
				$query .= " AND cta.view_date >= '".$date_from."'";
			}

			if ($date_to) {
				$query .= " AND cta.view_date <= '".$date_to."'";
			}

			$query .= "
                    GROUP BY DATE_FORMAT(cta.view_date, \"%H\")
                ) AS x
                ORDER BY quantity DESC
            ";
		}

		$connection = $this->getEntityManager()->getConnection();
		$statement = $connection->prepare($query);
		$statement->bindParam('client', $client, \PDO::PARAM_INT);

		$statement->execute();
		$result = $statement->fetchAll();

		return $result;
	}

	public function reportCta($maxResults, $offset, array $params = [], $count = false)
	{
		$maxReportLinesPoc  = $params['maxReportLinesPoc'];
		$filters            = $params['filters'];

		$dateFrom = new \DateTime($filters['dateFrom']->format('Y-m-d 00:00:00'));
		$dateTo = new \DateTime($filters['dateTo']->format('Y-m-d 23:59:59'));

		$query = $this->createQueryBuilder('cta')
			->where('cta.campaign = :campaignId')
			->andWhere('cta.type = :type')
			->andWhere('cta.viewDate >= :dateFrom')
			->andWhere('cta.viewDate <= :dateTo')
			->setParameter('campaignId', $filters['campaignId'])
			->setParameter('type', $filters['type'])
			->setParameter('dateFrom', $dateFrom)
			->setParameter('dateTo', $dateTo)
		;

		if ($count === true) {
			$query->select('COUNT(s.id)');

			if ($maxReportLinesPoc) {
				return 5;
			}

			return $query->getQuery()->getSingleScalarResult();
		}

		$query->select('cta');

		$query->setMaxResults(($maxReportLinesPoc) ? $maxReportLinesPoc : $maxResults);
		$query->setFirstResult($offset);

		return $query->getQuery()->getResult();
	}
}
