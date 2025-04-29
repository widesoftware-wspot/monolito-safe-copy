<?php

namespace Wideti\DomainBundle\Service\NasCustomMiddleware;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\NasCustomMiddleware\NasMiddlewares\NasMiddleware;
use Wideti\FrontendBundle\Factory\Nas;


class NasMiddlewareManagerImp implements NasMiddlewareManager
{
    /**
     * @var NasMiddleware[]
     */
    private $middlewareQueue;

    /**
     * @param string $vendorName
     * @param Nas $nas
     * @param array $params
     * @param Client $client
     * @return Nas
     */
    public function handleNas($vendorName, Nas $nas = null, $params = [], Client $client)
    {
        $modifiedNas = $nas;
        foreach ($this->middlewareQueue as $middleware) {
            $modifiedNas = $middleware->handleNas($vendorName, $nas, $params, $client);
        }

        return $modifiedNas;
    }

    /**
     * @param NasMiddleware $nasMiddleware
     * @return void
     */
    public function registerMiddleware(NasMiddleware $nasMiddleware)
    {
        $this->middlewareQueue[] = $nasMiddleware;
    }
}