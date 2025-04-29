<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\Vendor;

class VideoSkipRepository extends EntityRepository
{
    public function deleteByCampaignIdAndStep($campaignId, $step)
    {
        $query = "DELETE FROM video_skip WHERE campaign_id = :campaignId AND step = :step;";
        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);
        $statement->bindParam('campaignId', $campaignId, \PDO::PARAM_INT);
        $statement->bindParam('step', $step, \PDO::PARAM_STR);
        $statement->execute();
    }
}



