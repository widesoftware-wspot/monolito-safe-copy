<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\Client;

class ClientRepository extends EntityRepository
{
	public function findByDomains(array $domain = [])
	{
		return $this->createQueryBuilder('c')
			->where("c.domain IN (:domain)")
			->setParameter("domain", $domain)
			->getQuery()
			->getResult()
			;
	}

	public function getActiveClients()
	{
		return $this->createQueryBuilder('c')
			->where("c.status IN (1, 2)")
			->getQuery()
			->getResult()
			;
	}

    public function getClientsByClosingDate($closingDate)
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.closingDate = :closingDate')
            ->orWhere('c.closingDate = :closingDateAux')
            ->setParameter('closingDate', $closingDate)
            ->setParameter('closingDateAux', intval($closingDate))
            ->getQuery();

        return $qb->getResult();
    }

    public function getClientsOrdered($orderBy)
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.status IN (1, 2)')
            ->orderBy('c.domain', $orderBy)
            ->getQuery();

        return $qb->getResult();
    }

    public function getClientsTestingExpiringThisWeek()
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.pocEndDate >= :now')
            ->andWhere('c.pocEndDate <= :week')
            ->setParameter('now', new \DateTime('now'))
            ->setParameter('week', new \DateTime('+7 day'))
            ->orderBy('c.pocEndDate', 'ASC')
            ->getQuery();

        return $qb->getResult();
    }

    public function getClientsTestingExpireds()
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.pocEndDate < :now')
            ->setParameter('now', new \DateTime('now'))
            ->orderBy('c.pocEndDate', 'ASC')
            ->getQuery();

        return $qb->getResult();
    }

    public function listAllClients()
    {
        $query = $this->createQueryBuilder('c')
            ->select('c', 'm')
            ->leftJoin('c.module', 'm')
            ->getQuery();

        return $query;
    }

	/**
	 * @param null $client
	 * @return array|mixed
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
    public function listAllClientsAndUsersInPoc($client = null)
    {
        $query = $this->createQueryBuilder('c')
            ->select('c', 'u')
            ->leftJoin("c.users", "u", 'WITH', "u.ultimoAcesso > c.pocEndDate")
            ->orderBy('u.ultimoAcesso');

        if ($client) {
            $result = $query->AndWhere('c.id = :client')
                ->setParameter('client', $client)
                ->getQuery()
                ->getSingleResult();
            return $result;
        }

        $result = $query->Where('c.status = :status')
            ->setParameter('status', Client::STATUS_POC)
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function listAllClientsQuery($searchBy)
    {
        if (!is_null($searchBy) && $searchBy == 'poc_30_days') {
            $date = new \DateTime();
            $date = $date->sub(new \DateInterval('P1M'));
            return $this->createQueryBuilder('c')
                ->select('c')
                ->where('c.status = :poc')
                ->andWhere('c.created < :date')
                ->setParameter(':date', $date)
                ->setParameter(':poc', Client::STATUS_POC)
                ->getQuery();
        } elseif (!is_null($searchBy) && $searchBy == 'poc') {
            return $this->createQueryBuilder('c')
                ->select('c')
                ->where('c.status = :poc')
                ->setParameter(':poc', Client::STATUS_POC)
                ->getQuery();
        } elseif (!is_null($searchBy) && $searchBy == 'poc_no_ap_config') {
            $query = "SELECT c
                      FROM DomainBundle:Client c
                      WHERE c.id
                        NOT IN (
                          SELECT cl.id 
                          FROM DomainBundle:Client cl
                          INNER JOIN DomainBundle:AccessPoints ap WITH cl.id = ap.client
                          WHERE cl.status = 2
                        )";
            return $this->getEntityManager()->createQuery($query);
        } elseif (!is_null($searchBy) && $searchBy == 'poc_no_template') {
            $query = "SELECT c
                      FROM DomainBundle:Client c
                      WHERE c.id
                        NOT IN (
                          SELECT cl.id 
                          FROM DomainBundle:Client cl
                          INNER JOIN DomainBundle:Template tp WITH cl.id = tp.client
                          WHERE tp.created = tp.updated AND cl.status = 2
                        )";
            return $this->getEntityManager()->createQuery($query);
        } elseif (!is_null($searchBy) && $searchBy == 'poc_3_days') {
            $date = new \DateTime();
            $dateNow = $date;
            $dateNow = $dateNow->format('d-m-Y');
            $dateNow = \DateTime::createFromFormat('d-m-Y H:i:s', $dateNow.' 00:00:00');
            $date = $date->add(new \DateInterval('P3D'));
            $date = $date->format('d-m-Y');
            $date = \DateTime::createFromFormat('d-m-Y H:i:s', $date.' 00:00:00');
            return $this->createQueryBuilder('c')
                ->select('c')
                ->where('c.status = :poc')
                ->andWhere('c.pocEndDate BETWEEN :dateNow AND :date')
                ->setParameter(':date', $date)
                ->setParameter(':dateNow', $dateNow)
                ->setParameter(':poc', Client::STATUS_POC)
                ->getQuery();
        } else {
            return $this->createQueryBuilder('c')
                ->select('c')
                ->where('c.status = 2')
                ->getQuery();
        }

        return $this->createQueryBuilder('c')
            ->select('c')
            ->where('c.status = :poc')
            ->setParameter(':disabled', Client::STATUS_POC)
            ->getQuery();
    }

    /**
     * @return int
     */
    public function countAllDomains()
    {
        $query = $this->createQueryBuilder('c')
            ->select('c.domain')
            ->getQuery();

        return count($query->getResult());
    }


    /**
     * @return int
     */
    public function countAllInactiveClients()
    {
        $query = $this->createQueryBuilder('c')
            ->select('c')
            ->where('c.status = :active')
            ->setParameter(':active', 0)
            ->getQuery();

        return count($query->getResult());
    }

    /**
     * @return int
     */
    public function countAllActiveClients()
    {
        $query = $this->createQueryBuilder('c')
            ->select('c')
            ->where('c.status = :active')
            ->setParameter(':active', 1)
            ->getQuery();

        return count($query->getResult());
    }

    /**
     * @return int
     */
    public function countAllPocClients()
    {
        $query = $this->createQueryBuilder('c')
            ->select('c')
            ->where('c.status = :active')
            ->setParameter(':active', 2)
            ->getQuery();

        return count($query->getResult());
    }

    /**
     * @param $option
     * @param $value
     * @param $status
     * @return \Doctrine\ORM\Query
     */
    public function filterClient($option, $value, $plan, $status)
    {
        $query = $this->createQueryBuilder('c')
            ->select('c', 'm')
            ->leftJoin('c.module', 'm')
            ->leftJoin('c.users', 'u');

        if ($option == 'domain') {
            $query->where('c.domain LIKE :value');
        }

        if ($option == 'erpId') {
            $query->where('c.erpId = :value');
        }

        if ($option == 'company') {
            $query->where('c.company LIKE :value');
        }

        if ($option == 'adminData') {
            $query->where('u.nome LIKE :value or u.username LIKE :value');
        }

        if ($option !== null) {
            if ($option == 'erpId') {
                $query->setParameter('value', (int)$value);
            } else {
                $query->setParameter('value', "%$value%");
            }
        } else {
            $query = $this->createQueryBuilder('c')
                ->select('c', 'm')
                ->leftJoin('c.module', 'm')
                ->leftJoin('c.users', 'u')
                ->where('c.domain LIKE :value or c.company LIKE :value')
                ->orWhere('u.nome LIKE :value or u.username LIKE :value')
                ->setParameter('value', "%$value%");
        }

        if ($plan !== null) {
        	$query->andWhere('c.plan = :plan')
		        ->setParameter('plan', $plan);
        }

        if ($status !== null) {
            $query->andWhere('c.status = :status')
                ->setParameter('status', $status);

            if ($status === 0)
                $query->orderBy('c.updated', 'DESC');
        }
        return $query->getQuery();
    }

	/**
	 * @param $client
	 * @throws \Doctrine\DBAL\DBALException
	 */
    public function delete($client)
    {
        $clientId = $client->getId();

	    $this->configSafeUpdatesAndForeignKeyChecks(0);

	    $query = "";

	    if ($this->hasCampaign($clientId)) {
		    $query .= "DELETE FROM campaign_access_points_groups WHERE campaign_id IN (SELECT id FROM campaign WHERE client_id = $clientId);";
		    $query .= "DELETE FROM campaign_access_points WHERE campaign_id IN (SELECT id FROM campaign WHERE client_id = $clientId);";
		    $query .= "DELETE FROM campaign_hours WHERE campaign_id IN (SELECT id FROM campaign WHERE client_id = $clientId);";
		    $query .= "DELETE FROM campaign_media_image WHERE client_id = $clientId;";
		    $query .= "DELETE FROM campaign_media_video WHERE client_id = $clientId;";
		    $query .= "DELETE FROM campaign_views_aggregated WHERE client_id = $clientId;";
		    $query .= "DELETE FROM campaign_call_to_action WHERE campaign_id IN (SELECT id FROM campaign WHERE client_id = $clientId);";
		    $query .= "DELETE FROM call_to_action_access_data WHERE campaign_id IN (SELECT id FROM campaign WHERE client_id = $clientId);";

		    if ($this->hasCampaignView($clientId)) {
			    $query .= "DELETE FROM campaign_views WHERE campaign_id IN (SELECT id FROM campaign WHERE client_id = $clientId);";
		    }

		    $query .= "DELETE FROM campaign WHERE client_id = $clientId;";
	    }

	    $query .= "DELETE FROM api_egoi_access_points WHERE api_egoi_id IN (SELECT id FROM api_egoi WHERE client_id = $clientId);";
	    $query .= "DELETE FROM api_egoi WHERE client_id = $clientId;";

	    $query .= "DELETE FROM api_rdstation_access_points WHERE api_rdstation_id IN (SELECT id FROM api_rdstation WHERE client_id = $clientId);";
	    $query .= "DELETE FROM api_rdstation WHERE client_id = $clientId;";

	    $query .= "DELETE FROM blacklist WHERE client_id = $clientId;";

	    $query .= "DELETE FROM white_label WHERE client_id = $clientId;";

	    $query .= "DELETE FROM segmentation WHERE client_id = $clientId;";

        $query .= "DELETE FROM access_code_settings_access_points WHERE access_code_settings_id IN (SELECT id FROM access_code_settings WHERE client_id = $clientId);";
        $query .= "DELETE FROM access_code_settings WHERE client_id = $clientId;";
        $query .= "DELETE FROM access_code_access_points WHERE access_code_id IN (SELECT id FROM access_code WHERE client_id = $clientId);";
        $query .= "DELETE FROM access_code_codes WHERE access_code_id IN (SELECT id FROM access_code WHERE client_id = $clientId);";
        $query .= "DELETE FROM access_code WHERE client_id = $clientId;";

	    $query .= "DELETE FROM access_points WHERE client_id = $clientId;";
	    $query .= "DELETE FROM access_points_groups WHERE client_id = $clientId;";

	    $query .= "DELETE FROM template WHERE client_id = $clientId;";
	    $query .= "DELETE FROM client_configurations WHERE client_id = $clientId;";

	    $query .= "DELETE FROM module_configuration_value WHERE client_id = $clientId;";
        $query .= "DELETE FROM client_modules WHERE client_id = $clientId;";

        $query .= "DELETE FROM radcheck WHERE client_id = $clientId;";

        $query .= "DELETE FROM contract_users WHERE user_id IN (SELECT id FROM usuarios WHERE client_id = $clientId);";
        $query .= "DELETE FROM usuarios WHERE client_id = $clientId;";

        $query .= "DELETE FROM guest_auth_code WHERE guest_id IN (SELECT id FROM visitantes WHERE client_id = $clientId);";
        $query .= "DELETE FROM sms_historic WHERE guest_id IN (SELECT id FROM visitantes WHERE client_id = $clientId);";

        $query .= "DELETE FROM devices_entries WHERE guest_id IN (SELECT id FROM visitantes WHERE client_id = $clientId);";

        $query .= "DELETE FROM visitantes WHERE client_id = $clientId;";

        $query .= "DELETE FROM clients WHERE id = $clientId;";

	    $connection = $this->getEntityManager()->getConnection();
	    $statement  = $connection->prepare($query);

	    $statement->execute();
    }

    private function configSafeUpdatesAndForeignKeyChecks($status)
    {
        $this->foreignKeyChecks($status);
        $this->safeUpdates($status);
    }

    private function safeUpdates($status)
    {
        $query = "SET FOREIGN_KEY_CHECKS = {$status};";
        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);
        $statement->execute();
    }

    private function foreignKeyChecks($status)
    {
        $query  = "SET SQL_SAFE_UPDATES = {$status};";
        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);
        $statement->execute();
    }

    public function deleteAllGuestsAndAccountings($client)
    {
        $this->configSafeUpdatesAndForeignKeyChecks(0);
        $clientId = $client->getId();

        $query  = "DELETE FROM radcheck WHERE client_id = $clientId;";
        $query .= "DELETE FROM guest_auth_code WHERE guest_id IN (SELECT id FROM visitantes WHERE client_id = $clientId);";
        $query .= "DELETE FROM sms_historic WHERE guest_id IN (SELECT id FROM visitantes WHERE client_id = $clientId);";
        $query .= "DELETE FROM visitantes WHERE client_id = $clientId;";

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);

        $statement->execute();

//        $this->configSafeUpdatesAndForeignKeyChecks(1);
    }

    /**
     * @param $hash
     * @return array
     */
    public function getClientByHash($hash)
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'u')
            ->innerJoin("c.users", "u")
            ->where('c.changePlanHash = :hash')
            ->andWhere('u.username != :email')
            ->orderBy('u.dataCadastro', 'ASC')
            ->setParameter('hash', $hash)
            ->setParameter('email', 'contato@wideti.com.br')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
    }

	private function hasCampaign($clientId)
	{
		$query      = "SELECT COUNT(1) as count FROM campaign WHERE client_id = $clientId;";
		$connection = $this->getEntityManager()->getConnection();
		$statement  = $connection->prepare($query);
		$statement->execute();
		$result     = $statement->fetchAll();
		return $result[0]['count'] === "0" ? false : true;
	}

	private function hasCampaignView($clientId)
	{
		$query      = "SELECT COUNT(1) as count FROM campaign_views WHERE campaign_id IN (select id from campaign where client_id = $clientId);";
		$connection = $this->getEntityManager()->getConnection();
		$statement  = $connection->prepare($query);
		$statement->execute();
		$result     = $statement->fetchAll();
		return $result[0]['count'] === "0" ? false : true;
	}

	public function updateAllClientsToReportSentEqualFalse()
    {
        $query      = "UPDATE clients SET report_sent = 0 WHERE id >= 1;";
        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);
        $statement->execute();
    }

    public function spotsManagerFilter($query, array $clientIds) {
		$qb = $this->createQueryBuilder('c');

		if (!empty($query)) {
			$qb->where('c.domain LIKE :value OR c.company LIKE :value')
			->setParameter(':value', "%$query%");
		}

		$qb->select('c')
			->andWhere('c.id IN (:ids)')
			->setParameter('ids', $clientIds)
			->orderBy('c.domain', 'asc');

		return $qb->getQuery()->getResult();
	}
}
