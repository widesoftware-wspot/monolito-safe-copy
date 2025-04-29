<?php

namespace Wideti\DomainBundle\Service\Client;

use Wideti\DomainBundle\Helpers\StringHelper;

class SelectClientByDomainServiceImp implements SelectClientByDomainService
{
    /**
     * @var ClientService
     */
    private $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    /**
     * @param $domain
     * @return mixed|object|null
     */
    public function get($domain)
    {
        $domain = StringHelper::getClientDomainByUrl($domain);
        $client = $this->clientService->getClientByDomain($domain);
        return $client;
    }
}
