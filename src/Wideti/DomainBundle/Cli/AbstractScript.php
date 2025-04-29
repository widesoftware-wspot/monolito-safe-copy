<?php

namespace Wideti\DomainBundle\Cli;

use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;

require_once '../../../../../app/AppKernel.php';

/**
 * Class AbstractScript
 * @package Wideti\DomainBundle\Cli
 */
abstract class AbstractScript implements Script
{
    /**
     * @var Kernel $kernel
     */
    protected $kernel;
    /**
     * @var Application $application
     */
    protected $application;
    /**
     * @var ContainerInterface $container
     */
    protected $container;
    /**
     * @var EntityManager|object $entityManager
     */
    protected $entityManager;
    /**
     * @var DocumentManager|object $documentManager
     */
    protected $documentManager;
    /**
     * @var object|ElasticSearch $elasticSearch
     */
    protected $elasticSearch;
    /**
     * @var ConsoleOutput $output
     */
    protected $output;

    /**
     * AbstractScript constructor.
     * @param $environment
     */
    public function __construct($environment)
    {
        $this->kernel = new \AppKernel($environment, true);
        $this->kernel->boot();

        $this->output          = new ConsoleOutput();
        $this->application     = new Application($this->kernel);
        $this->container       = $this->application->getKernel()->getContainer();
        $this->documentManager = $this->container->get("doctrine.odm.mongodb.document_manager");
        $this->entityManager   = $this->container->get("doctrine.orm.entity_manager");
        $this->elasticSearch   = $this->container->get("core.service.elastic_search");
    }
}