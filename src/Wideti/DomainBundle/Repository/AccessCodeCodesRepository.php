<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Dto\AccessCodeDto;
use Wideti\DomainBundle\Entity\AccessCode;
use Wideti\DomainBundle\Entity\AccessCodeCodes;

class AccessCodeCodesRepository extends EntityRepository
{
    /**
     * @param $codeId
     * @return object|null
     */
    public function getCodeById($codeId)
    {
        return $this->findOneBy([
            'code' => $codeId
        ]);
    }

    /**
     * @param AccessCodeDto $accessCodeDto
     * @param $inputCode
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findByCode(AccessCodeDto $accessCodeDto, $inputCode)
    {
        $ids = implode(', ', $accessCodeDto->getAccessCodeIds());

        $query = "
            SELECT *
            FROM access_code_codes code
            INNER JOIN access_code a ON code.access_code_id = a.id
            WHERE code.code = :inputCode
            AND code.access_code_id IN ($ids)
        ";

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);
        $statement->bindParam('inputCode', $inputCode, \PDO::PARAM_STR);
        $statement->execute();
        $result     = $statement->fetchAll();

        return $result ? $result[0] : false;
    }

    /**
     * @param Guest $guest
     * @param $inputCode
     * @throws \Doctrine\DBAL\DBALException
     */
    public function setAccessCodeAsUsed(Guest $guest, $inputCode)
    {
        $this->configSafeUpdatesAndForeignKeyChecks(0);

        $status = AccessCodeCodes::USED;
        $dateTime = date('Y-m-d H:i:s');
        $guest = $guest->getMysql();

        $query = "
            UPDATE access_code_codes 
            SET used = {$status}, used_time = '{$dateTime}', guest = {$guest}
            WHERE code = :inputCode; 
        ";

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);
        $statement->bindParam('inputCode', $inputCode, \PDO::PARAM_STR);
        $statement->execute();

        $this->configSafeUpdatesAndForeignKeyChecks(1);
    }

    /**
     * @param $status
     * @throws \Doctrine\DBAL\DBALException
     */
    private function configSafeUpdatesAndForeignKeyChecks($status)
    {
        $query  = "SET SQL_SAFE_UPDATES = :status;";
        $query .= "SET FOREIGN_KEY_CHECKS = :status;";

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);
        $statement->bindParam('status', $status, \PDO::PARAM_INT);
        $statement->execute();
    }

    public function findByCodeUsed($accessCode)
    {

        $query = "
            SELECT *
            FROM access_code_codes code
            WHERE code.code = :accessCode AND code.used_time IS NOT NULL
        ";

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);
        $statement->bindParam('accessCode', $accessCode, \PDO::PARAM_STR);
        $statement->execute();
        $result     = $statement->fetchAll();
        return $result ? $result[0] : null;
    }

    public function getRadcheckExpirationByCode($accessCode)
    {
        $query = "
            SELECT r.value FROM access_code_codes acc
                INNER JOIN radcheck r ON r.username = acc.guest
                WHERE acc.code = :accessCode
        ";

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);
        $statement->bindParam('accessCode', $accessCode, \PDO::PARAM_STR);
        $statement->execute();
        $result     = $statement->fetchAll();
        return $result ? $result[0] : null;
    }
}
