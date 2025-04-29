<?php

namespace Wideti\DomainBundle\Helpers;

use Doctrine\ORM\EntityManager;

class CountAccessPointFriendlyNamePending
{
    private $em;

    private $session;

    public function __construct(EntityManager $entityManager, $session)
    {
        $this->em = $entityManager;
        $this->session = $session;
    }

    public function count()
    {
        $accessPointPending = $this->em->getRepository("DomainBundle:AccessPoints")
                                   ->findByfriendlyName('');

        $this->session->set('accessPointPendingName', \count($accessPointPending));

        return true;
    }
}
