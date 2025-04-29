<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\Client;

class CampaignHoursRepository extends EntityRepository
{
    public function validateHours($campaign)
    {
        $qb = $this->createQueryBuilder('h')
            ->where('h.campaign = :campaign')
            ->andWhere('h.startTime <= :now')
            ->andWhere('h.endTime >= :now')
            ->setParameter('campaign', $campaign)
            ->setParameter('now', date('H:i'));

        $query = $qb->getQuery();

        if ($query->getOneOrNullResult()) {
            return true;
        }

        return false;
    }

    public function deleteAllByClient(Client $client)
    {
        $clientId = $client->getId();

        $query  = "SET SQL_SAFE_UPDATES = 0;";
        $query .= "
            DELETE ch
            FROM campaign_hours ch
            INNER JOIN campaign c on ch.campaign_id = c.id
            WHERE c.client_id = $clientId;
        ";

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);

        return $statement->execute();
    }
}
