<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\Client;

class SmsMarketingRepository extends EntityRepository
{
    public function search(array $filters)
    {
        $client = $filters["client"];
        $status = isset($filters["status"]) ? $filters["status"] : null;

        $qb = $this->createQueryBuilder("s")
            ->select("s")
            ->where("s.client = :client")
            ->setParameter("client", $client);

        if (!$status) {
            $qb->andWhere("s.status != 'removed'");
        } elseif ($status != "all") {
            $qb
                ->andWhere("s.status = :status")
                ->setParameter("status", $status);
        }

        $result = $qb->getQuery();
        return $result->getResult();
    }
}
