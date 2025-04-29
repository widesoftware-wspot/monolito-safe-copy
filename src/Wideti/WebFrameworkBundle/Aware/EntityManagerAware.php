<?php

namespace Wideti\WebFrameworkBundle\Aware;

use Doctrine\ORM\EntityManager;

/**
 * Symfony Server Setup: - [ setEntityManager, ["@doctrine.orm.entity_manager"] ]
 */
trait EntityManagerAware
{
    /**
     * @var EntityManager
     */
    protected $em;

    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }
}
