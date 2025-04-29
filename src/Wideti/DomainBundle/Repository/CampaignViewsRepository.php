<?php


namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\Client;

class CampaignViewsRepository extends EntityRepository
{
	public function getMostViewedHoursCustom(
		$client = null,
		$campaign = null,
		$date_from = null,
		$date_to = null
	) {
		$query = "
			SELECT campaign_id AS id, COUNT(*) AS quantidade
			FROM campaign_views
		";

		if ($campaign) {
			$campaign = join(',', $campaign);
			$query .= "
				WHERE campaign_id IN ({$campaign})
			";
		} else {
			$query .= "
			WHERE campaign_id IN (
				SELECT id FROM campaign WHERE client_id = {$client}
			)
		";
		}

		if ($date_from) {
			$query .= "AND view_time >= '{$date_from} 00:00:00'";
		}

		if ($date_to) {
			$query .= "AND view_time <= '{$date_to} 23:59:59'";
		}

		$query .= "
			GROUP BY campaign_id
			ORDER BY quantidade DESC
			LIMIT 10";

		$connection = $this->getEntityManager()->getConnection();
		$statement = $connection->prepare($query);
		$statement->bindParam('client', $client, \PDO::PARAM_INT);

		$statement->execute();
		$result = $statement->fetchAll();

		return $result;
	}

	public function getMostViewedHours(
		$client = null,
		$campaign = null,
		$date_from = null,
		$date_to = null
	) {
		$query = "
            SELECT c.id, c.name, ca.total
            FROM (
                SELECT campaign_id, SUM(total) as total
                FROM campaign_views_aggregated
                WHERE client_id = :client
            ";

        if ($campaign) {
            $campaign = join(',', $campaign);
            $query .= " AND campaign_id IN (".$campaign.")";
        }

        if ($date_from) {
            $query .= " AND last_aggregation_time >= '".date_format($date_from, 'Y-m-d 00:00:00')."'";
        }

        if ($date_to) {
            $query .= " AND last_aggregation_time <= '".date_format($date_to, 'Y-m-d 23:59:59')."'";
        }

        $query .= "
                GROUP BY campaign_id
            ) AS ca
            INNER JOIN campaign c ON ca.campaign_id = c.id
            ORDER BY ca.total DESC
            LIMIT 10
            ";

		$connection = $this->getEntityManager()->getConnection();
		$statement = $connection->prepare($query);
		$statement->bindParam('client', $client, \PDO::PARAM_INT);

		$statement->execute();
		return $statement->fetchAll();
	}

    public function getMostViewedHoursByCampaign($campaign, $params)
    {
        $query = "
                SELECT campaign_id, step, sum(total) as total
                FROM campaign_views_aggregated
                WHERE campaign_id = :campaign
                ";

        if (array_key_exists('date_from', $params) && $params['date_from'] != '') {
            $dateFrom = \DateTime::createFromFormat('d/m/Y', $params['date_from']);
            $dateFrom = $dateFrom->format('Y-m-d 00:00:00');
            $query .= "AND last_aggregation_time >= '" .$dateFrom."' ";
        }

        if (array_key_exists('date_to', $params) && $params['date_to'] != '') {
            $dateTo = \DateTime::createFromFormat('d/m/Y', $params['date_to']);
            $dateTo = $dateTo->format('Y-m-d 23:59:59');
            $query .= "AND last_aggregation_time <= '" .$dateTo."' ";
        }

        $query .= "GROUP BY step ORDER BY step ASC";

        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->prepare($query);
        $statement->bindParam('campaign', $campaign, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function deleteAllByClient(Client $client)
    {
        $clientId = $client->getId();

        $query  = "SET SQL_SAFE_UPDATES = 0;";
        $query .= "
            DELETE cv
            FROM campaign_views cv
            INNER JOIN campaign c on cv.campaign_id = c.id
            WHERE c.client_id = $clientId;
        ";

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);

        return $statement->execute();
    }

    public function save($campaignView)
    {
        $em = $this->getEntityManager();
        $em->persist($campaignView);
        $em->flush();
    }

    public function getCampaignViews($campaign, $type, $filters)
    {
        $connection = $this->getEntityManager()->getConnection();

        $statement = $connection->prepare("
            SELECT v.guest,
                   v.access_point, 
                   DATE(v.view_time) view_time,
                   COUNT(*) quantity
            FROM campaign_views v
            WHERE v.type = {$type}
               AND v.campaign_id = {$campaign}
               AND DATE(v.view_time) >= '{$filters['date_from']}'
               AND DATE(v.view_time) <= '{$filters['date_to']}'
          	GROUP BY v.guest,
                  DATE(v.view_time)
          	ORDER BY DATE(v.view_time)");

        $statement->execute();

        if ($statement->rowCount() > 0) {
            return $statement->fetchAll();
        }

        return [];
    }

	public function campaignViewedByGuest($guestMacAddress)
	{
		$connection = $this->getEntityManager()->getConnection();

		$statement = $connection->prepare("
            SELECT c.name,
                   v.type,
                   v.guest,
                   v.access_point, 
                   DATE(v.view_time) view_time,
                   COUNT(*) quantity
              FROM campaign c,
                   campaign_views v
             WHERE c.id = v.campaign_id
               AND v.guest = '{$guestMacAddress}'
          GROUP BY v.campaign_id,
                   v.type,
                  DATE(v.view_time)");

		$statement->execute();

		if ($statement->rowCount() > 0) {
			return $statement->fetchAll();
		}

		return [];
	}

    public function getAggregatedCountBetweenDates($dateFrom, $dateTo, $clientId = null)
    {
        $connection = $this->getEntityManager()->getConnection();

        $query = "
            SELECT c.client_id, cv.campaign_id, cv.type as step, count(cv.type) as total
            FROM campaign_views cv
            INNER JOIN campaign c ON cv.campaign_id = c.id
            WHERE cv.view_time >= '{$dateFrom}'
            AND cv.view_time < '{$dateTo}'
        ";

        if ($clientId) {
            $query .= "AND c.client_id = {$clientId} ";
        }

        $query .= "GROUP BY cv.campaign_id, cv.type;";

        $statement = $connection->prepare($query);

        $statement->execute();
        return $statement->fetchAll();
    }
}
