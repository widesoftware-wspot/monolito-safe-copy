<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\SMSBillingControl;

class SmsBillingControlRepository extends EntityRepository
{
    /**
     * @return \Doctrine\DBAL\Connection
     */
    private function getDatabaseConnection()
    {
        return $this->getEntityManager()->getConnection();
    }

    /**
     * @param Client $client
     * @param $closingDateStart
     * @param $closingDateEnd
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getBillingHistoric(Client $client, $closingDateStart, $closingDateEnd)
    {
        $query = "
            SELECT s.*,
                   c.company,
                   c.domain,
                   c.closing_date,
                   c.erp_id 
              FROM sms_billing_control s,
                   clients c
             WHERE s.client_id = {$client->getId()}
               AND s.closing_date_start BETWEEN '{$closingDateStart}' AND '{$closingDateEnd}'
               AND s.client_id = c.id
          ORDER BY s.closing_date_reference DESC, c.domain";

        $connection = $this->getDatabaseConnection();
        $statement = $connection->prepare($query);
        $statement->execute();

        if ($statement->rowCount() > 0) {
            return $statement->fetchAll();
        }

        return false;
    }

    /**
     * @param Client $client
     * @param $closingDateStart
     * @param $closingDateEnd
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getSMSData(Client $client, $closingDateStart, $closingDateEnd)
    {
		$query = "
            SELECT COUNT(*) sentSMSNumber, sms_cost
            FROM sms_historic
            WHERE client_id = {$client->getId()} 
            AND DATE(sent_date) BETWEEN '{$closingDateStart}' AND '{$closingDateEnd}'
        ";

        $connection = $this->getDatabaseConnection();
        $statement  = $connection->prepare($query);
        $statement->execute();

        $smsData = $statement->fetchAll();

        $sms['sent_sms_number'] = $smsData[0]['sentSMSNumber'];
        $sms['cost_per_sms'] = str_replace(',', '.', $client->getSmsCost());
        $sms['amount_to_pay'] = $sms['sent_sms_number'] * $sms['cost_per_sms'];

        return $sms;
    }

	public function getLastSendingDate(Client $client)
	{
		$lastSendingDate = null;
		$query = "
			SELECT closing_date_end lastClosingDateEnd 
			FROM sms_billing_control 
			WHERE client_id = {$client->getId()}
			ORDER BY id DESC
			LIMIT 1";

		$connection = $this->getDatabaseConnection();
		$statement  = $connection->prepare($query);

		$statement->execute();

		if ($statement->rowCount() > 0) {
			$smsSendingData = $statement->fetchAll();
			if (!is_null($smsSendingData[0]['lastClosingDateEnd'])) {
				return new \DateTime(date('Y-m-d', strtotime($smsSendingData[0]['lastClosingDateEnd'])));
			}
		}

		return $lastSendingDate;
	}

	public function getFirstSmsSentDate(Client $client)
	{
		$lastSendingDate = null;
		$query = "
			SELECT DATE_FORMAT(sent_date, \"%Y-%m-%d\") as firstSentDate
			FROM sms_historic
			WHERE client_id = {$client->getId()}
			ORDER BY id ASC
			LIMIT 1";

		$connection = $this->getDatabaseConnection();
		$statement  = $connection->prepare($query);

		$statement->execute();

		if ($statement->rowCount() > 0) {
			$data = $statement->fetchAll();

			if (!is_null($data[0]['firstSentDate'])) {
				return new \DateTime(date('Y-m-d', strtotime($data[0]['firstSentDate'])));
			}
		}

		return $lastSendingDate;
	}

    /**
     * @param Client $client
     * @param $closingDateStart
     * @param $closingDateEnd
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function checkIfSMSBillingExists(
        Client $client,
        $closingDateStart,
        $closingDateEnd
    )
    {
        $connection = $this->getDatabaseConnection();

        $query = "
          SELECT id
          FROM sms_billing_control
          WHERE client_id = {$client->getId()}
          AND closing_date_start = '{$closingDateStart}'
          AND closing_date_end = '{$closingDateEnd}'
        ";

        $statement = $connection->prepare($query);
        $statement->execute();

        return ($statement->rowCount() > 0);
    }

    /**
     * @param $field
     * @param $value
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getDataByFilter($condition)
    {
        $connection = $this->getDatabaseConnection();

        $query = "
            SELECT s.*,
				c.company,
				c.domain, 
				c.closing_date, 
				c.erp_id
			FROM sms_billing_control s, clients c
			WHERE {$condition}
			AND s.client_id = c.id
			ORDER BY s.closing_date_reference DESC, c.domain";

        $statement = $connection->prepare($query);

        $statement->execute();

        if ($statement->rowCount() > 0) {
            $smsData = [];

            while ($data = $statement->fetchAll()) {
                $smsData[] = $data;
            }

            return $smsData;
        }

        return [];
    }

    /**
     * @return array|Client[]
     */
    public function getClientsForBilling()
    {
        return $this->getEntityManager()
            ->getRepository('DomainBundle:Client')
            ->getActiveClients()
	        ;
    }

    /**
     * @param SMSBillingControl $SMSBillingControl
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create(SMSBillingControl $SMSBillingControl)
    {
        $this->getEntityManager()->persist($SMSBillingControl);
        $this->getEntityManager()->flush();
    }

    /**
     * @param $id
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function changeStatus($id)
    {
        $pendingBill = $this->getEntityManager()
            ->getRepository('DomainBundle:SMSBillingControl')
            ->find([ 'id' => $id ]);

        if ($pendingBill) {
            $pendingBill->setStatus(!$pendingBill->getStatus());
            $this->getEntityManager()->persist($pendingBill);
            $this->getEntityManager()->flush();

            return !$pendingBill->getStatus() ? "Pendente" : "Enviado";
        }

        return false;
    }
}