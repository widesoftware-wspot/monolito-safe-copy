<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use League\Period\Period;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\GuestSocial;

class GuestsRepository extends EntityRepository
{
    public function deleteByGuest($guestId)
    {
        $query  = "SET SQL_SAFE_UPDATES = 0;";
        $query .= "DELETE FROM guest_auth_code WHERE guest_id = $guestId;";
        $query .= "DELETE FROM radcheck WHERE username = $guestId;";
        $query .= "DELETE FROM sms_historic WHERE guest_id = $guestId;";
        $query .= "DELETE FROM devices_entries WHERE guest_id = $guestId;";
        $query .= "DELETE FROM visitantes WHERE id = $guestId;";

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);

        $statement->execute();
    }

    public function deleteByClient($clientId)
    {
        $query  = "SET SQL_SAFE_UPDATES = 0;";
        $query .= "DELETE FROM guest_auth_code WHERE guest_id IN (SELECT id FROM visitantes WHERE client_id = $clientId);";
        $query .= "DELETE FROM radcheck WHERE client_id = $clientId;";
        $query .= "DELETE FROM sms_historic WHERE guest_id IN (SELECT id FROM visitantes WHERE client_id = $clientId);";
        $query .= "DELETE FROM visitantes WHERE client_id = $clientId;";

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);

        $statement->execute();
    }

    public function getCustomClient(Client $client, $guestId)
    {
        $clientId = $client->getId();

        $query = "
            SELECT id
            FROM visitantes
            WHERE client_id = :clientId
            AND id >= :guestId
            LIMIT 20000
        ";

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);
        $statement->bindParam('clientId', $clientId, \PDO::PARAM_INT);
        $statement->bindParam('guestId', $guestId, \PDO::PARAM_INT);

        $statement->execute();

        $result = $statement->fetchAll();

        return $result;
    }

    public function getLastGuestIdByClient(Client $client)
    {
        $clientId = $client->getId();

        $query = "
            SELECT id
            FROM visitantes
            WHERE client_id = :clientId
            ORDER BY id DESC
            LIMIT 1
        ";

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);
        $statement->bindParam('clientId', $clientId, \PDO::PARAM_INT);

        $statement->execute();

        $result = $statement->fetchAll();

        return $result ? (int)$result[0]['id'] : null;
    }

    public function countByClient(Client $client)
    {
        $clientId = $client->getId();

        $query = "
            SELECT count(*) as total
            FROM visitantes
            WHERE client_id = :clientId
        ";

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);
        $statement->bindParam('clientId', $clientId, \PDO::PARAM_INT);

        $statement->execute();

        $result = $statement->fetchAll();

        return (int)$result[0]['total'];
    }
}
