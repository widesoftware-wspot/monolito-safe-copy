<?php

namespace Wideti\PanelBundle\Service;

use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

class MongoDatabaseService
{
    use MongoAware;
    private $clientDomain;

    public function evaluatePasswordActivation($isEnablePasswordAuthentication)
    {
        $guests = $this->mongo
            ->createQueryBuilder('Wideti\DomainBundle\Document\Guest\Guest')
            ->field('_id')
            ->getQuery()
            ->getSingleResult();
        if (!$guests || ($isEnablePasswordAuthentication && $guests)) {
            return true;
        }
        return false;
    }

    public function getGuests() {
        return $this->mongo
            ->createQueryBuilder('Wideti\DomainBundle\Document\Guest\Guest')
            ->field('_id')
            ->getQuery()
            ->getSingleResult();
    }

    public function setDefaultDatabaseOnMongo($domain)
    {
        $this->clientDomain = $domain;
        $manager    = $this->mongo;
        $manager
            ->getConfiguration()
            ->setDefaultDB($domain);

        $this->mongo->create(
            $manager->getConnection(),
            $manager->getConfiguration(),
            $manager->getEventManager()
        );
    }
}
