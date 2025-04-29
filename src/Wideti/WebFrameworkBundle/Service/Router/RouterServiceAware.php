<?php
namespace Wideti\WebFrameworkBundle\Service\Router;

/**
 * Symfony Server Setup: - [ setRouterService, ["@web_framwework.service.router"] ]
 */
trait RouterServiceAware
{
    /**
     * @var RouterService
     */
    protected $routerService;

    public function setRouterService(RouterService $routerService)
    {
        $this->routerService = $routerService;
    }
}
