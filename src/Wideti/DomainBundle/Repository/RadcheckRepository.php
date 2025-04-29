<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;

class RadcheckRepository extends EntityRepository
{
    public function deleteExpiration($client)
    {
        try {
            $clientId = $client->getId();

            $query = "DELETE FROM radcheck WHERE attribute = 'Expiration' AND client_id = $clientId";

            $connection = $this->getEntityManager()->getConnection();
            $statement  = $connection->prepare($query);

            $statement->execute();
        } catch (\Exception $e) {
            throw new \Exception("Fail to remove Expiration time: " . $e->getMessage());
        }

        return true;
    }

    public function deleteAllExpirationTimeByGuest($clientId, $groupId)
    {
        try {
            $query = "DELETE FROM radcheck WHERE group_id = '$groupId' AND client_id = $clientId";
            $connection = $this->getEntityManager()->getConnection();
            $statement = $connection->prepare($query);
            $statement->execute();
        } catch (\Exception $e) {
            throw new \Exception("Fail to remove Expiration time: "
                . $e->getMessage());
        }
    }

    public function deleteGuestExpiration(Guest $guest, Client $client)
    {
        try {
            $clientId = $client->getId();
            $userName = $guest->getMysql();

            $query = "DELETE FROM radcheck 
                      WHERE attribute = 'Expiration' 
                      AND client_id = $clientId 
                      AND username = $userName";

            $connection = $this->getEntityManager()->getConnection();
            $statement  = $connection->prepare($query);

            $statement->execute();
        } catch (\Exception $e) {
            throw new \Exception(
                "Fail to remove Expiration time of guest username {$guest->getMysql()} " . $e->getMessage()
            );
        }
    }
}
