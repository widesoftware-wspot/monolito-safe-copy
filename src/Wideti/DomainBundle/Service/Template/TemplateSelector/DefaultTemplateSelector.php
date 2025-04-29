<?php

namespace Wideti\DomainBundle\Service\Template\TemplateSelector;

use Doctrine\ORM\EntityManager;
use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Template;
use Wideti\FrontendBundle\Factory\Nas;

class DefaultTemplateSelector implements TemplateSelector
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Nas $nas
     * @param Client $client
     * @param Campaign $campaign
     * @return Template
     */
    public function select(Nas $nas = null, Client $client, Campaign $campaign = null)
    {
        return $this->entityManager
            ->getRepository("DomainBundle:Template")
            ->defaultTemplate($client->getId());
    }
}
