<?php

namespace Wideti\DomainBundle\Service\Dns;

use Wideti\DomainBundle\Service\Dns\DnsService;

/**
 *
 * Usage: - [ setDnsService, [@core.service.dns] ]
 */
trait DnsServiceAware
{
    /**
     * @var DnsService
     */
    protected $dnsService;

    public function setDnsService(DnsService $service)
    {
        $this->dnsService = $service;
    }
}