<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\Client;

class SmsHistoricRepository extends EntityRepository
{
    public function reportSms($client, $maxResults, $offset, array $params = [], $count = false)
    {
        // TODO acrescentar filtro de guest que revogou consentimento
        $maxReportLinesPoc  = $params['maxReportLinesPoc'];
        $filters            = array_key_exists('filters', $params) ? $params['filters'] : null;

        $query = $this->createQueryBuilder('s')
            ->innerJoin('s.guest', 'g')
            ->innerJoin('g.client', 'c')
            ->where('c.id = :client')
            ->setParameter('client', $client)
        ;

        if (isset($filters['date_from'])) {
            $query
                ->andWhere('s.sentDate >= :dateTo')
                ->setParameter('dateTo', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query
                ->andWhere('s.sentDate <= :dateFrom')
                ->setParameter('dateFrom', $filters['date_to']);
        }

        if ($count === true) {
            $query->select('COUNT(s.id)');

            if ($maxReportLinesPoc) {
                return 5;
            }

            return $query->getQuery()->getSingleScalarResult();
        }

        $query->select('s');

        $query->setMaxResults(($maxReportLinesPoc) ? $maxReportLinesPoc : $maxResults);
        $query->setFirstResult($offset);

        return $query->getQuery()->getResult();
    }

    public function getSmsBillingByClient(Client $client, $dateFrom = null, $dateTo = null)
    {
        $qb = $this->createQueryBuilder('s')
            ->select('c.company, g.id, s.message_id, s.bodyMessage, s.sentTo, s.sentDate, c.id as id_client')
            ->innerJoin('s.guest', 'g')
            ->innerJoin('g.client', 'c')
            ->where('c.id = :client')
            ->orderBy('id_client')
            ->setParameter('client', $client->getId());

        if ($dateFrom) {
            $qb
                ->andWhere('s.sentDate >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo) {
            $qb
                ->andWhere('s.sentDate <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        $qb->setMaxResults(20000);

        $result = $qb->getQuery();

        return $result->getArrayResult();
    }

    public function getSmsBillingByMonth(Client $client, $month)
    {
        $qb = $this->createQueryBuilder('s')
            ->select('c.company, g.id, s.message_id, s.bodyMessage, s.sentTo, s.sentDate')
            ->innerJoin('s.guest', 'g')
            ->innerJoin('g.client', 'c')
            ->where('c.id = :client')
            ->andWhere('s.sentDate LIKE :period')
            ->setParameter('period', $month.'%')
            ->setParameter('client', $client->getId());

        $result = $qb->getQuery();

        return $result->getArrayResult();
    }

    public function deleteByClient(Client $client)
    {
        $clientId = $client->getId();

        $query  = "SET SQL_SAFE_UPDATES = 0;";
        $query .= "DELETE FROM sms_historic WHERE guest_id IN (SELECT id FROM visitantes WHERE client_id = $clientId);";

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);

        $statement->execute();
    }
}
