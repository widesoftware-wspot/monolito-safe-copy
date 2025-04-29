<?php
namespace Wideti\DomainBundle\Service\EntityLogger;

use Doctrine\Common\PropertyChangedListener;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Elasticsearch\ClientBuilder;
use Wideti\DomainBundle\Document\Guest\Guest;

class EntityLoggerService
{
    use SecurityAware;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var \Elasticsearch\Client
     */
    protected $client;

    /**
     * @var \JMS\Serializer\Serializer
     */
    protected $serializer;

    /**
     * @var PropertyChangedListener
     */
    protected $uow;

    /**
     * Listen this entities in specific actions
     *
     * @var array
     */
    protected $entities = [
        'create' => [
            'Wideti\\DomainBundle\\Entity\\Guests',
            'Wideti\\DomainBundle\\Document\\Guest\\Guest',
            'Wideti\\DomainBundle\\Entity\\Campaign',
            'Wideti\\DomainBundle\\Entity\\Users'
        ],
        'update' => [
            'Wideti\\DomainBundle\\Document\\Guest\\Guest',
            'Wideti\\DomainBundle\\Entity\\Campaign',
            'Wideti\\DomainBundle\\Entity\\Users'
        ],
        'delete' => [
            'Wideti\\DomainBundle\\Entity\\Guests',
            'Wideti\\DomainBundle\\Document\\Guest\\Guest',
            'Wideti\\DomainBundle\\Entity\\Campaign',
            'Wideti\\DomainBundle\\Entity\\Users'
        ]
    ];

    public function __construct($hosts)
    {
        $this->hosts = $hosts;
        $this->index = ElasticSearch::LAST_12_MONTHS;

        $this->client = ClientBuilder::create()
            ->setHosts($hosts)
            ->setRetries(ElasticSearch::NUMBER_OF_RETRIES)
            ->build()
        ;
    }

    /**
     * Method that event will dispatch
     *
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $this->em   = $args->getEntityManager();
        $this->uow  = $this->em->getUnitOfWork();
        $this->uow->computeChangeSets();

        foreach ($this->uow->getScheduledEntityInsertions() as $entity) {
            if (in_array(get_class($entity), $this->entities['create'])) {
                $this->creating($entity, $this->uow->getEntityChangeSet($entity));
            }
        }

        foreach ($this->uow->getScheduledEntityUpdates() as $entity) {
            if (in_array(get_class($entity), $this->entities['update'])) {
                $this->updating($entity, $this->uow->getEntityChangeSet($entity));
            }
        }

        foreach ($this->uow->getScheduledEntityDeletions() as $entity) {
            if (in_array(get_class($entity), $this->entities['delete'])) {
                $this->deleting($entity);
            }
        }
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->dm  = $args->getDocumentManager();
        $this->uow = $this->dm->getUnitOfWork();
        $this->uow->computeChangeSets();

        foreach ($this->uow->getScheduledDocumentUpdates() as $document) {
            if (in_array(get_class($document), $this->entities['update'])) {
                $this->updating($document, $this->uow->getDocumentChangeSet($document));
            }
        }
    }

    public function creating($entity, $changes)
    {
        $changeSet = [
            'id'      => $entity->getId(),
            'changes' => $changes
        ];

        if (isset($changeSet['changes']['value'])) {
            if ($changeSet['changes']['value'][0] == $changeSet['changes']['value'][1]) {
                return null;
            }
        }

        $computer  = new EntityComputer($entity, $changeSet);

        if ($entity instanceof Campaign) {
            $computer->setUow($this->uow);
        }

        $changeSet = $computer->getComputedChanges();
        
        return $this->log([
            'action'    => 'create',
            'changeset' => $changeSet,
            'module'    => $this->identifyModule($entity)
        ]);
    }

    public function updating($entity, $changes)
    {
        if ($entity instanceof Guest) {
            $changeSet = [
                'id'      => $entity->getMysql(),
                'changes' => $changes
            ];
        } else {
            $changeSet = [
                'id'      => $entity->getId(),
                'changes' => $changes
            ];
        }

        if (isset($changeSet['changes']['value'])) {
            if ($changeSet['changes']['value'][0] == $changeSet['changes']['value'][1]) {
                return null;
            }
        }

        $computer  = new EntityComputer($entity, $changeSet);

        if ($entity instanceof Campaign) {
            $computer->setUow($this->uow);
        }
        $changeSet = $computer->getComputedChanges();

        return $this->log([
            'action'    => 'update',
            'changeset' => $changeSet,
            'module'    => $this->identifyModule($entity)
        ]);
    }

    public function deleting($entity)
    {
        return $this->log([
            'action'    => 'delete',
            'changeset' => json_decode($this->serializer->serialize($entity, 'json'), true),
            'module'    => $this->identifyModule($entity)
        ]);
    }

    public function log(array $params)
    {
        return;
        $document = [
            'module'    => $params['module'],
            'action'    => $params['action'],
            'client'    => $this->clientData(),
            'user'      => $this->userData(),
            'date'      => date('Y-m-d H:i:s'),
            'changeset' => $params['changeset']
        ];

        $response = $this->client->index([
            'index' => ElasticSearch::LOG,
            'type'  => 'changelog',
            'body'  => $document
        ]);

        return $response;
    }

    public function userData()
    {
        if (!$this->getUser() instanceof Users) {
            return [];
        }

        $user = $this->getUser();

        return [
            'id'    => $user->getId(),
            'name'  => $user->getNome()
        ];
    }

    public function clientData()
    {
        $user = $this->getUser();

        if (!$user instanceof Users) {
            return [];
        }
        if (!$user->getClient() instanceof Client) {
            return [];
        }

        $client = $user->getClient();

        return [
            'id'        => $client->getId(),
            'company'   => $client->getCompany(),
            'domain'    => $client->getDomain()
        ];
    }

    public function identifyModule($module)
    {
        $class  = explode('\\', get_class($module));
        $module = end($class);
        return $module;
    }

    /**
     * Inject Serializer
     *
     * @param \JMS\Serializer\Serializer $serializer
     */
    public function setSerializer(\JMS\Serializer\Serializer $serializer)
    {
        $this->serializer = $serializer;
    }

    public function setClient(\Elasticsearch\Client $client)
    {
        $this->client = $client;
    }
}
