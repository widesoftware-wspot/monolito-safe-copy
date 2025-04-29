<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Dto\AccessCodeDto;
use Wideti\DomainBundle\Entity\AccessCode;
use Wideti\DomainBundle\Entity\AccessCodeCodes;
use Wideti\DomainBundle\Entity\Client;

class AccessCodeRepository extends EntityRepository
{
    /**
     * @param AccessCode $accessCode
     * @param null $apsId
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function checkIfAlreadyExists(AccessCode $accessCode, $apsId = null)
    {
        $client = $accessCode->getClient();
        $status = AccessCode::ACTIVE;

        $query = "
            SELECT *
            FROM access_code
            WHERE client_id = {$client->getId()}
            AND enable = {$status}
            AND step <> '{$accessCode->getStep()}'
            AND in_access_points <> 1
            OR (
                client_id = {$client->getId()}
                AND enable = {$status}
                AND step <> '{$accessCode->getStep()}'
                AND in_access_points = 1
            )";

        if ($apsId) {
            $aps = implode(', ', $apsId);
            $query = "
                SELECT *
                FROM access_code a
                INNER JOIN access_code_access_points ap ON ap.access_code_id = a.id
                WHERE a.client_id = {$client->getId()}
                AND a.enable = {$status}
                AND a.step <> '{$accessCode->getStep()}'
                AND a.id IN ({$aps})
                OR (
                    a.client_id = {$client->getId()}
                    AND a.enable = {$status}
                    AND a.step <> '{$accessCode->getStep()}'
                    AND a.in_access_points <> 1
                )
            ";
        }

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);
        $statement->execute();
        $result     = $statement->fetchAll();

        return $result ? true : false;
    }

    /**
     * @param AccessCode $accessCode
     * @return int
     */
    public function countUsed(AccessCode $accessCode)
    {
        $codesUsed  = 0;
        $codes      = $accessCode->getCodes();

        if (count($codes) == 0) return 0;

        /**
         * @var AccessCodeCodes $code
         */
        foreach ($codes as $code) {
            if ($code->getUsed() == true) {
                $codesUsed++;
            }
        }

        return $codesUsed;
    }

    /**
     * @param AccessCodeDto $accessCodeDto
     * @param $inputCode
     * @return bool|AccessCodeDto
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findAccessCodeByCode(AccessCodeDto $accessCodeDto, $inputCode)
    {
        $hasCode = $this->_em->getRepository('DomainBundle:AccessCodeCodes')
            ->findByCode($accessCodeDto, $inputCode);

        if (!$hasCode) {
            return false;
        }

        $accessCode = $this->find($hasCode['access_code_id']);

        if ($accessCode->getType() == AccessCode::TYPE_PREDEFINED) {
            $accessCodeDto->setAccessCodeParams([
                'step'           => $accessCode->getStep(),
                'type'           => AccessCode::TYPE_PREDEFINED,
                'connectionTime' => $accessCode->getConnectionTime(),
                'used'           => $hasCode['used'],
                'code'           => $inputCode
            ]);

            return $accessCodeDto;
        }

        if ($accessCode->getType() == AccessCode::TYPE_RANDOM) {
            /**
             * @var AccessCodeCodes $code
             */
            foreach ($accessCode->getCodes() as $code) {
                if (strcmp($code->getCode(), $inputCode) == 0) {
                    $accessCodeDto->setAccessCodeParams([
                        'step'              => $accessCode->getStep(),
                        'type'              => AccessCode::TYPE_RANDOM,
                        'code'              => $inputCode,
                        'connectionTime'    => $accessCode->getConnectionTime(),
                        'usedTime'          => $code->getUsedTime(),
                        'username'          => $code->getGuest(),
                        'used'              => $code->getUsed()
                    ]);

                    return $accessCodeDto;
                }
            }
        }

        return false;
    }

    /**
     * @param AccessCode $accessCode
     * @param $preDefinedCode
     * @param $newPreDefinedCode
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updatePreDefinedCode(AccessCode $accessCode, $preDefinedCode, $newPreDefinedCode)
    {
        $query = "
            UPDATE access_code_codes SET code = '{$newPreDefinedCode}'
            WHERE access_code_id = {$accessCode->getId()}
            AND code = '{$preDefinedCode}'
        ";

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);
        $statement->execute();
    }

    /**
     * @param Client $client
     * @param $step
     * @param null $apId
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getAccessCodeByStepAndAccessPoint(Client $client, $step, $apId = null)
    {
        $dateNow = date('Y-m-d H:i:s');
        $status  = AccessCode::ACTIVE;
        $ap      = $apId ?: 0;

        $query = "
            SELECT a.*
            FROM access_code a
            LEFT JOIN access_code_access_points ac_ap ON ac_ap.access_code_id = a.id
            WHERE a.client_id = {$client->getId()}
            AND a.enable = {$status}
            AND a.step = '{$step}'
            AND (
                a.period_from IS NULL
                OR a.period_from <= '{$dateNow}'
            )
            AND (
                a.period_to IS NULL
                OR a.period_to >= '{$dateNow}'
            )
            AND (
                a.in_access_points IS NULL
                OR a.in_access_points = 0
                OR (
                    a.in_access_points = 1
                    AND ac_ap.access_point_id IN ({$ap})
                )
            )
        ";

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);
        $statement->execute();
        $results    = $statement->fetchAll();

        return $results;
    }
}
