<?php

namespace Wideti\DomainBundle\Service\NasManager;

use Symfony\Bridge\Monolog\Logger;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;
use Wideti\DomainBundle\Service\NasManager\Dto\RadiusPolicy\RadiusPolicy;
use Wideti\DomainBundle\Service\NasManager\Dto\RadiusPolicy\RadiusPolicyBuilder;
use Wideti\DomainBundle\Service\PolicyWriter\PolicyWriter;
use Wideti\DomainBundle\Service\RadiusPolicy\RadiusPolicyService;
use Wideti\FrontendBundle\Factory\Nas;

class NasPolicyStep implements NasStepInterface
{
    /**
     * @var PolicyWriter
     */
    private $policyWriterManager;
    /**
     * @var RadiusPolicyService
     */
    private $radiusPolicyService;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * NasPolicyStep constructor.
     * @param PolicyWriter $policyWriterManager
     * @param RadiusPolicyService $radiusPolicyService
     * @param Logger $logger
     */
    public function __construct(
        PolicyWriter $policyWriterManager,
        RadiusPolicyService $radiusPolicyService,
        Logger $logger
    ) {
        $this->policyWriterManager = $policyWriterManager;
        $this->radiusPolicyService = $radiusPolicyService;
        $this->logger = $logger;
    }

    public function process(Guest $guest, Nas $nas = null, Client $client)
    {
        $policyBuilder = RadiusPolicyBuilder::create();
        $this->policyWriterManager->write($nas, $guest, $client, $policyBuilder);
        $policy = $policyBuilder->build();

        $policyCreated = $this->radiusPolicyService->save($policy);

        if (!$policyCreated) {
            $this->logger->addWarning('Policy wasn\'t created for guest: ' . $guest->getId(), $nas->getVendorRawParameters());
        }

        $nas->setRadiusPolicy($policyCreated);
    }

}
