<?php

namespace Wideti\DomainBundle\Service\ClientSelector;

use Wideti\DomainBundle\Service\ClientSelector\ClientSelectorService;

/**
 *
 * Usage: - [ setClientSelectorService, ["@core.service.client_selector"] ]
 */
trait ClientSelectorServiceAware
{
    /**
     * @var ClientSelectorService
     */
    protected $clientSelectorService;

    public function setClientSelectorService(ClientSelectorService $service)
    {
        $this->clientSelectorService = $service;
    }
}
