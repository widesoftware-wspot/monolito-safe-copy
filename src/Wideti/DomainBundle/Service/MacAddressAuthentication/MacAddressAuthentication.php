<?php

namespace Wideti\DomainBundle\Service\MacAddressAuthentication;

use Wideti\DomainBundle\Entity\Client;
use Wideti\FrontendBundle\Factory\Nas;

interface MacAddressAuthentication
{
    public function process(Client $client, Nas $nas);
}
