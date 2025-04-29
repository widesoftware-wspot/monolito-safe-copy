<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\Blacklist;
use Wideti\DomainBundle\Entity\Client;

class BlacklistRepository extends EntityRepository
{
    /**
     * @param Blacklist $blacklist
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveOnBlackList(Blacklist $blacklist) {
        $entityManager = $this->getEntityManager();
        $clientRepository = $entityManager->getRepository(Client::class);
        $client = $clientRepository->find($blacklist->getClient());
        $blacklist->setClient($client);

        $created = $blacklist->getCreated();
        $dateTime = \DateTime::createFromFormat('y-m-d H:i:s', $created);
        $blacklist->setCreated($dateTime);

        $entityManager->persist($blacklist);
        $entityManager->flush();
    }

    /**
     * @param $macAddress
     * @return |null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findByMacAddress ($macAddress, $clientId) {
        $query = "SELECT * FROM blacklist WHERE mac_address = \"$macAddress\" AND client_id = $clientId";
        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->prepare($query);
        $statement->execute();
        $result = $statement->fetchAll();
        return $result ? $result[0] : null;
    }

    /**
     * @param $macAddress
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteByMacAddres($macAddress)
    {
        $entityManager = $this->getEntityManager();

        $blacklist = $entityManager->getRepository(Blacklist::class)->findOneBy(['macAddress' => $macAddress]);

        if ($blacklist) {
            $entityManager->remove($blacklist);
            $entityManager->flush();
        }
    }


    /**
     * @param Blacklist $blacklist
     * @param $oldMacValue
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update(Blacklist $blacklist, $oldMacValue)
    {
        $entityManager = $this->getEntityManager();
        $existingBlacklist = $entityManager->getRepository(Blacklist::class)->findOneBy([
            'macAddress' => $oldMacValue,
            'client' => $blacklist->getClient(),
        ]);

        $clientRepository = $entityManager->getRepository(Client::class);
        $client = $clientRepository->find($blacklist->getClient());
        $dateTime = \DateTime::createFromFormat('y-m-d H:i:s', $blacklist->getCreated());

        if ($existingBlacklist) {
            $existingBlacklist->setClient($client);
            $existingBlacklist->setCreated($dateTime);
            $existingBlacklist->setMacAddress($blacklist->getMacAddress());

            $entityManager->persist($existingBlacklist);
            $entityManager->flush();
        }
    }

	/**
	 * @param $clientId
	 * @param $filters
	 * @return \Doctrine\ORM\Query
	 */
    public function pagination ($clientId, $filters) {

        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder = $queryBuilder->select()
            ->where("p.client = :clientId")
            ->setParameter("clientId",$clientId)
            ->orderBy("p.created", "DESC");

        if (isset($filters['macAddress'])) {
            $queryBuilder->andWhere("p.macAddress= :macAddress");
            $queryBuilder->setParameter("macAddress", strtoupper($filters['macAddress']));
        }

        $blacklists = $queryBuilder->getQuery();

        return $blacklists;
    }
}
