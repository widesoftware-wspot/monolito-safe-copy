<?php
namespace Wideti\DomainBundle\Service\MongoDatabase;

use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Wideti\DomainBundle\Helpers\StringHelper;

class MongoDatabase
{
    /**
     * @var DocumentManager
     */
    protected $manager;

    public function __construct(DocumentManager $manager)
    {
        $this->manager = $manager;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $db = $this->defineDB($event);
        $manager    = $this->manager;

        $manager
            ->getConfiguration()
            ->setDefaultDB($db)
        ;

        $this->manager->create(
            $manager->getConnection(),
            $manager->getConfiguration(),
            $manager->getEventManager()
        );
    }

    private function defineDB(GetResponseEvent $event) {
        $host = $event->getRequest()->getHost();
        if(strpos($host, "wspot.com.br")|| strpos($host, "mambowifi")){
            $hostArray  = explode(".", $event->getRequest()->getHost());
            return $hostArray[0];
        }
        return StringHelper::slugDomain($host);
    }
}
