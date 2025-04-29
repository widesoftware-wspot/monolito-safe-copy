<?php

namespace Wideti\DomainBundle\Service\Client;

use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\StringHelper;

class SelectClientByRequestServiceImp implements SelectClientByRequestService
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
     * @param Request $request
     * @return Client
     */
    public function get(Request $request)
    {
        $domain = StringHelper::getClientDomainByUrl($request->getHttpHost());
        $client = $this->clientService->getClientByDomain($domain);
        return $client;
    }
}
