<?php

namespace Wideti\DomainBundle\Service\PolicyWriter;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\NasManager\Dto\RadiusPolicy\RadiusPolicyBuilder;
use Wideti\FrontendBundle\Factory\Nas;

class PolicyWriterManager implements PolicyWriter
{
    /**
     * @var PolicyWriter[]
     */
    private $policyWriters;

    /**
     * @param Nas $nas
     * @param Guest $guest
     * @param Client $client
     * @param RadiusPolicyBuilder $builder
     * @return void
     */
    public function write(Nas $nas, Guest $guest, Client $client, RadiusPolicyBuilder $builder)
    {
        foreach ($this->policyWriters as $writer) {
            $writer->write($nas, $guest, $client, $builder);
        }
    }

    public function registryWriter(PolicyWriter $policyWriter)
    {
        $this->policyWriters[] = $policyWriter;
    }
}
