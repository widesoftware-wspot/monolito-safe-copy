<?php

namespace Wideti\WebFrameworkBundle\Aware;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Wideti\DomainBundle\Helpers\DateTimeHelper;

/**
 * Symfony Server Setup: - [ setMongo, ["@doctrine_mongodb.odm.default_document_manager"] ]
 */
trait MongoAware
{
    /**
     * @var DocumentManager
     */
    public $mongo;

    public function setMongo(DocumentManager $mongo)
    {
        $this->mongo = $mongo;
    }

    public function getMongoTimezone()
    {
        return DateTimeHelper::timezoneOffset("America/Sao_Paulo", "UTC");
    }
}
