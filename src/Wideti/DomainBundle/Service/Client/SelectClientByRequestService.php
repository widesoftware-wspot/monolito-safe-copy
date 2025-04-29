<?php

namespace Wideti\DomainBundle\Service\Client;

use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Entity\Client;

interface SelectClientByRequestService
{
    /**
     * @param Request $request
     * @return Client
     */
    public function get(Request $request);
}
