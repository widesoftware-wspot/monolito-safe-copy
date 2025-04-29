<?php

namespace Wideti\DomainBundle\Service\NasCustomMiddleware\NasMiddlewares;

use Wideti\DomainBundle\Entity\Client;
use Wideti\FrontendBundle\Factory\Nas;

interface NasMiddleware
{
    /**
     * @param string $vendorName
     * @param Nas $nas
     * @param array $params
     * @param Client $client
     * @return Nas
     */
    public function handleNas($vendorName, Nas $nas = null, $params = [], Client $client);
}