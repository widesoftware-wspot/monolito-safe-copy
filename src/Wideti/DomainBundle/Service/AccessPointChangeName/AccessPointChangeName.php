<?php
namespace Wideti\DomainBundle\Service\AccessPointChangeName;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;
use Wideti\DomainBundle\Service\Sns\SnsService;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class AccessPointChangeName
{
    use SecurityAware;
    use SessionAware;
    use LoggerAware;

    /**
     * @var SnsService
     */
    protected $sns;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var UnitOfWork
     */
    protected $uow;

    protected $index;

    public function __construct()
    {
        $this->index = ElasticSearch::ALL;
    }

    /**
     * Listen this entities in specific actions
     *
     * @var array
     */
    protected $entities = [
        'update' => [
            'Wideti\\DomainBundle\\Entity\\AccessPoints',
        ]
    ];

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

        foreach ($this->uow->getScheduledEntityUpdates() as $entity) {
            if (in_array(get_class($entity), $this->entities['update'])) {
                $this->updating($entity);
            }
        }
    }

    public function updating($entity)
    {
        $changes = $this->uow->getEntityChangeSet($entity);

        if (isset($changes["friendlyName"])) {
            $message = $changes["friendlyName"][0] . "|" .
                $changes["friendlyName"][1] ."|" .
                $this->getLoggedClient()->getId() . "|".
                $this->index
            ;

            try {
                $this->sns->getClient()->publish([
                    "TopicArn" => $this->sns->getArn(),
                    "Message"  => $message
                ]);
            } catch (\Exception $e) {
                $this->logger->addCritical('Fail to send message to SNS. Message: '. $e->getMessage());
            }
        }
    }

    public function setSnsService(SnsService $sns)
    {
        $this->sns = $sns;
    }
}
