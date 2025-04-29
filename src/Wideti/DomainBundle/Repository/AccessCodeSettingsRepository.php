<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;

class AccessCodeSettingsRepository extends EntityRepository
{
    public function getSettingsByFilter($filter)
    {
        $client = $filter['client'];
        $status = $filter['enable'];
        $ap     = $filter['accessPoint'];

        $query = "
            SELECT a.*
            FROM access_code_settings a
            LEFT JOIN access_code_settings_access_points a_ap ON a_ap.access_code_settings_id = a.id
            WHERE a.client_id = {$client->getId()}
            AND a.enable_free_access = {$status}
            AND (
                a.in_access_points IS NULL
                OR a.in_access_points = 0
                OR (
                    a.in_access_points = 1
                    AND a_ap.access_point_id IN ({$ap})
                )
            )
            GROUP BY a.id
            ;
        ";

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);
        $statement->execute();
        $result     = $statement->fetchAll();

        return $result ? $result[0] : false;
    }
}
