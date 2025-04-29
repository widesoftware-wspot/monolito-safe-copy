<?php

namespace Wideti\DomainBundle\Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wideti\DomainBundle\Entity\Client;

abstract class WspotTestCase extends WebTestCase
{
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var Client
     */
    private $client;
    private $domain;

    /**
     * @var DocumentManager
     */
    private $documentManager;
    private $configuration;

    public function setUp()
    {
        self::bootKernel();
        $this->container = static::$kernel->getContainer();
        $this->entityManager = $this->container->get('doctrine.orm.default_entity_manager');
        $this->documentManager = $this->container->get('doctrine_mongodb.odm.default_document_manager');
        $this->domain = $this->container->getParameter("test_client_domain");

        $this->client = $this
            ->entityManager
            ->getRepository('DomainBundle:Client')
            ->findOneBy([
                'domain' => $this->domain
            ]);

        $this->selectMongoDatabase($this->client->getDomain());

        $this->configuration = [
            'confirmation_sms'      => 0,
            'confirmation_email'    => 0,
            'enable_block_per_time' => 0
        ];
    }

    private function selectMongoDatabase($domain)
    {
        $manager = $this->documentManager;

        $manager
            ->getConfiguration()
            ->setDefaultDB($domain);

        $this->documentManager->create(
            $manager->getConnection(),
            $manager->getConfiguration(),
            $manager->getEventManager()
        );
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @return DocumentManager
     */
    public function getDocumentManager()
    {
        return $this->documentManager;
    }

    /**
     * @return mixed
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param mixed $configuration
     * @param $value
     */
    public function setConfiguration($configuration, $value)
    {
        $this->configuration[$configuration] = $value;
    }



}
