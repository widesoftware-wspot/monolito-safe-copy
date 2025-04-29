<?php


namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Service\CampaignViews\Dto\AggregatedViewsDto;

class CampaignViewsAggregatedRepository extends EntityRepository
{
    public function getLastAggregation()
    {
        $connection = $this->getEntityManager()->getConnection();

        $query = "
            SELECT *
            FROM campaign_views_aggregated
            ORDER BY last_aggregation_time DESC
            LIMIT 1
        ";

        $statement = $connection->prepare($query);
        $statement->execute();

        if ($statement->rowCount() > 0) {
            return $statement->fetchAll()[0];
        }

        return null;
    }

    public function getLastItemAggregatedByCondition(AggregatedViewsDto $dto)
    {
        $connection = $this->getEntityManager()->getConnection();

        $lastAggregatedTime = (new \DateTime($dto->getLastAggregatedTime()))->format('Y-m-d');

        $query = "
            SELECT *
            FROM campaign_views_aggregated
            WHERE client_id = {$dto->getClientId()}
            AND campaign_id = {$dto->getCampaignId()}
            AND step = {$dto->getStep()}
            AND DATE(last_aggregation_time) = '{$lastAggregatedTime}'
            ORDER BY id DESC
            LIMIT 1
        ";

        $statement = $connection->prepare($query);
        $statement->execute();

        if ($statement->rowCount() > 0) {
            return $statement->fetchAll()[0];
        }

        return null;
    }

    public function updateExistingRecord($id, $lastAggregatedTime, $total)
    {
        $query = "
            UPDATE campaign_views_aggregated 
            SET last_aggregation_time = '{$lastAggregatedTime}', total = {$total}
            WHERE id = {$id}; 
        ";

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);
        $statement->execute();
    }
}
