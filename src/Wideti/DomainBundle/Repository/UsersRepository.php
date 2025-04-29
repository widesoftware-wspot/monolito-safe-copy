<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;

class UsersRepository extends EntityRepository
{
    public function count($client, $filter = null, $value = null, $me = '')
    {
        $qb  = $this
            ->createQueryBuilder('u')
            ->select('count(u.username)')
            ->innerJoin('u.client', 'c', 'WITH', 'c.id = :client')
            ->where('u.username != :me')
            ->andWhere('u.status IN (0,1)')
            ->setParameter('me', $me)
            ->setParameter('client', $client);

        if ($filter) {
            switch ($filter) {
                case 'nome':
                    $qb->andWhere('u.nome LIKE :nome');
                    $qb->setParameter('nome', "%$value%");
                    break;
                case 'email':
                    $qb->andWhere('u.username LIKE :email');
                    $qb->setParameter('email', "%$value%");
                    break;
            }
        }

        $qb->andWhere('u.username != :username');
        $qb->setParameter('username', Users::USER_DEFAULT);

        $query = $qb->getQuery();

        return $query->getSingleScalarResult();
    }

    /**
     * @param $username
     * @param Client $client
     * @return bool
     */
    public function exists($username, Client $client) {
        return (bool) $this
            ->createQueryBuilder('u')
            ->select('count(u.username)')
            ->where('u.username = :email')
            ->setParameter('email', $username)
            ->andWhere('u.status IN (0,1)')
            ->andWhere('u.client = :client')
            ->setParameter('client', $client)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function listAllUsers(
        $client,
        $maxResults = null,
        $offset = null,
        $filter = null,
        $value = null,
        $me = null
    ) {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.dataCadastro', 'DESC')
            ->innerJoin('u.client', 'c', 'WITH', 'c.id = :client')
            ->where("u.username != :me")
            ->andWhere('u.status IN (0,1)')
            ->setParameter('client', $client)
            ->setParameter("me", $me)
        ;

        if ($filter) {
            switch ($filter) {
                case 'nome':
                    $qb->andWhere('u.nome LIKE :nome');
                    $qb->setParameter('nome', "%$value%");
                    break;
                case 'email':
                    $qb->andWhere('u.username LIKE :email');
                    $qb->setParameter('email', "%$value%");
                    break;
            }
        }

        $qb->andWhere('u.username != :username');
        $qb->setParameter('username', Users::USER_DEFAULT);

        if ($maxResults) {
            $qb->setMaxResults($maxResults);
        }

        if ($offset) {
            $qb->setFirstResult($offset);
        }

        $query = $qb->getQuery();

        return $query->getResult();
    }

    public function loginByClient($username, $client)
    {
        $q = $this
            ->createQueryBuilder('u')
            ->select('u, r')
            ->where('u.username = :username')
            ->andWhere('u.status = 1')
            ->andWhere('u.createdAtOauth = 0')
            ->andWhere('u.erpId is NULL')
            ->innerJoin('u.role', 'r')
            ->innerJoin('u.client', 'c', 'WITH', 'c.id = :client')
            ->setParameter('username', $username)
            ->setParameter('client', $client)
            ->getQuery();

        try {
            // The Query::getSingleResult() method throws an exception
            // if there is no record matching the criteria.
            $user = $q->getSingleResult();
        } catch (NoResultException $e) {
            $message = sprintf(
                'Unable to find an active admin AcmeUserBundle:User object identified by "%s".',
                $username
            );
            throw new UsernameNotFoundException($message, 0, $e);
        }

        return $user;
    }

    public function getUsersToSyncMailchimp()
    {
        $qb = $this
            ->createQueryBuilder('u')
            ->select('u.username')
            ->where('c.status != :status')
            ->innerJoin('u.client', 'c')
            ->innerJoin('u.role', 'r')
            ->setParameter('status', Client::STATUS_INACTIVE)
            ->getQuery();

        $results = [];
        foreach ($qb->getResult() as $result) {
            array_push($results, strtolower($result['username']));
        }

        return array_unique($results);
    }

    public function checkIfHasAccess($clientId)
    {
        $emailDefault = Users::USER_DEFAULT;

        $query = "
                SELECT COUNT(1) AS count
                FROM usuarios
                WHERE client_id = :clientId
                AND username != :emailDefault
                AND ultimo_acesso IS NOT NULL
                ";

        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->prepare($query);
        $statement->bindParam('clientId', $clientId, \PDO::PARAM_INT);
        $statement->bindParam('emailDefault', $emailDefault, \PDO::PARAM_STR);
        $statement->execute();
        $result = $statement->fetchAll();

        return ($result[0]['count'] == 0) ? false : true;
    }

    public function getOneUser($client)
    {
        $q = $this
            ->createQueryBuilder('u')
            ->select()
            ->where('u.username <> :default')
            ->innerJoin('u.client', 'c', 'WITH', 'c.id = :client')
            ->setMaxResults(1)
            ->setParameter('default', Users::USER_DEFAULT)
            ->setParameter('client', $client)
            ->getQuery();

        $user = $q->getResult();

        return $user;
    }

    public function verifyIfExitsAnotherUsernameRegistered($client, $username, $exludeUserId)
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u')
            ->where('u.client = :client')
            ->andWhere('u.username = :username')
            ->andWhere('u.id <> :id')
            ->setParameter('client', $client)
            ->setParameter('username', $username)
            ->setParameter('id', $exludeUserId);

        $query = $qb->getQuery();

        return $query->getResult();
    }

    public function delete($clientId, $userName)
    {
        $connection = $this->getEntityManager()->getConnection();

        $queries = [
            "DELETE contract_users.* FROM contract_users, usuarios WHERE usuarios.id = contract_users.user_id " .
                "AND usuarios.client_id = {$clientId} AND usuarios.username = '{$userName}';",

            "DELETE FROM usuarios WHERE client_id = {$clientId} AND username = '{$userName}';"
        ];

        foreach ($queries as $query) {
            $statement  = $connection->prepare($query);
            $statement->execute();
        }

    }

}