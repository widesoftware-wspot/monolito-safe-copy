<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Wideti\DomainBundle\Entity\AuditLog;
use Wideti\DomainBundle\Entity\Users;


class AuditLogRepository extends EntityRepository
{
    public function findByClientId($clientId)
    {
        return $this->findBy(['clientId' => $clientId], ['createdAt' => 'DESC']);
    }

    public function findByEventType($eventType, $clientId)
    {
        return $this->findBy(
            ['eventType' => $eventType, 'client' => $clientId],
            ['createdAt' => 'DESC']
        );
    }

    public function findByUserAndClient($userId, $clientId)
    {
        return $this->findBy(
            ['sourceId' => $userId, 'clientId' => $clientId],
            ['createdAt' => 'DESC']
        );
    }

    public function findWithFilters($clientId, $dateFrom = null, $dateTo = null, $eventType = null, $userId = null, $filterCompanyAdmins = true)
    {

        $qb = $this->createQueryBuilder('a')
            ->where('a.clientId = :clientId')
            ->setParameter('clientId', $clientId);

        if ($dateFrom) {
            $qb->andWhere('a.createdAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo) {
            $qb->andWhere('a.createdAt <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        if ($eventType) {
            $qb->andWhere('a.eventType = :eventType')
                ->setParameter('eventType', $eventType);
        }

        if ($userId) {
            $qb->andWhere('a.sourceId = :userId')
                ->setParameter('userId', $userId);
        }

        if ($filterCompanyAdmins) {
            $companyAdminRoles = [
                Users::ROLE_SUPORT_LIMITED,
                Users::ROLE_SUPER_ADMIN,
                Users::ROLE_MANAGER,
            ];

            $qb->join('Wideti\DomainBundle\Entity\Users', 'u', 'WITH', 'u.id = a.sourceId')
            ->andWhere('u.role NOT IN (:excludedRoles)')
            ->setParameter('excludedRoles', $companyAdminRoles);
        }

        return $qb->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(AuditLog $auditLog)
    {
        $this->_em->persist($auditLog);
        $this->_em->flush();
    }
}
