<?php

namespace Wideti\DomainBundle\Service\Client;

/**
 * Usage: - [ setClientService, ["@core.service.client"] ]
 */
trait ClientServiceAware
{
    /**
     * @var ClientService
     */
    protected $clientService;

    public function setClientService(ClientService $service)
    {
        $this->clientService = $service;
    }
}
