<?php


namespace Wideti\DomainBundle\Repository;


use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\DataControllerAgent;

class DataControllerAgentRepository extends EntityRepository
{
    public function save(DataControllerAgent $dataControllerAgent)
    {
        $this->_em->merge($dataControllerAgent);
        $this->_em->flush();
        return $dataControllerAgent;
    }

    /**
     * @param Client $client
     * @return DataControllerAgent
     */
    public function getDataControllerAgentByClient(Client $client)
    {
        return $this->findOneBy(['client' => $client]);
    }
}