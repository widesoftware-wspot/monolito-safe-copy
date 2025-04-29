<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;

class TimezoneRepository extends EntityRepository
{
    public function getCurrentTimeBasedOnTimezone($timezone)
    {
        $query = "SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(UTC_TIMESTAMP()) + tz.gmt_offset, '%H:%i:%s') AS local_time
                    FROM `timezone` tz JOIN `zone` z
                    ON tz.zone_id=z.zone_id
                    WHERE tz.time_start <= UNIX_TIMESTAMP(UTC_TIMESTAMP()) AND z.zone_name='".$timezone."'
                    ORDER BY tz.time_start DESC LIMIT 1;";
        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->prepare($query);
        $statement->execute();
        $result = $statement->fetchAll();
        return $result;
    }
}