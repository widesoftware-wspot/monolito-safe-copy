<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\Client;

class SmsBillingHistoricRepository extends EntityRepository
{
    public function getLastBillingDate(Client $client)
    {
        $clientId = $client->getId();

        $query = "
            SELECT date as last_billing
            FROM sms_billing_historic
            WHERE client_id = :clientId
            ORDER BY date DESC
            LIMIT 1";

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);
        $statement->bindParam('clientId', $clientId, \PDO::PARAM_INT);

        $statement->execute();

        $result = $statement->fetchAll();

        return $result[0]['last_billing'];
    }
}
