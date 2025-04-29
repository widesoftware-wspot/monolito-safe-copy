<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\ControllersUnifi;
use Wideti\DomainBundle\Entity\ClientsControllersUnifi;

/**
 * Class ControllersUnifiRepository
 * @package Wideti\DomainBundle\Repository
 */
class ControllersUnifiRepository extends EntityRepository
{
    /**
     * @param $address
     * @param $port
     * @return array
     */
    public function getControllerByUnique($address, $port)
    {
        $qb = $this->createQueryBuilder('ctrl')
            ->where('ctrl.address = :address')
            ->andWhere('ctrl.port = :port')
            ->setParameter('address', $address)
            ->setParameter('port', $port);
        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * @param $clientId
     * @return array
     */
    public function getControllerByClientId($clientId)
    {
        $query = "
            SELECT 
                ctrl.id,ctrl.address,ctrl.port,ctrl.username,ctrl.password,ctrl.is_mambo
            FROM 
                controllers_unifi ctrl
                INNER JOIN clients_controllers_unifi ccu
                    ON ctrl.id = ccu.unifi_id
            WHERE 
                ccu.client_id = :clientId
            ORDER BY 
                ccu.id DESC
            LIMIT 1
        ";

        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->prepare($query);
        $statement->bindParam('clientId', $clientId, \PDO::PARAM_INT);
        $statement->setFetchMode(\PDO::FETCH_CLASS, \Wideti\DomainBundle\Entity\ControllersUnifi::class );
        $statement->execute();
        $result = $statement->fetchAll();
        return $result;
    }

    /**
     * @param ControllersUnifi $controllersUnifi
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(ControllersUnifi $controllerUnifi) {
        $em = $this->getEntityManager();
        $em->persist($controlerUnifi);
        $em->flush();
    }

	/**
	 * @param ControllersUnifi $controllersUnifi
	 * @return bool
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function update($controllersUnifi)
	{
        $query = "UPDATE controllers_unifi SET address=:address,port=:port,username=:username,password=:password WHERE id=:id";
		$connection = $this->getEntityManager()->getConnection();
		$statement  = $connection->prepare($query);
        
        $address = $controllersUnifi->getAddress();
        $port = $controllersUnifi->getPort();
        $username = $controllersUnifi->getUsername();
        $password = $controllersUnifi->getPassword();
        $id = $controllersUnifi->getId();

        $statement->bindParam('address', $address, \PDO::PARAM_STR);
        $statement->bindParam('port', $port, \PDO::PARAM_INT);
        $statement->bindParam('username', $username, \PDO::PARAM_STR);
        $statement->bindParam('password', $password, \PDO::PARAM_STR);
        $statement->bindParam('id', $id, \PDO::PARAM_INT);
        
		return $statement->execute();
	}

    /**
     * @return array
     */
    public function queryAllUnifiControllersMamboActive()
    {
        $qb = $this->createQueryBuilder('ctrl')
            ->select('ctrl.id, ctrl.address, ctrl.port')
            ->where('ctrl.isMambo = true')
            ->andWhere('ctrl.active = true')
            ->orderBy('ctrl.id', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $id
     * @return array
     */
    public function deleteById($id)
    {
        $delete = $this->createQueryBuilder("ctrl")
            ->delete()
            ->where("ctrl.id = :id")
            ->setParameter("id", $id)
        ;
        $delete->getQuery()->execute();
    }
}
