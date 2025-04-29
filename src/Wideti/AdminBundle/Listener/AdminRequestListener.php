<?php

namespace Wideti\AdminBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\WebFrameworkBundle\Aware\RouterAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class AdminRequestListener
{
    use SessionAware;
    use RouterAware;

    /**
     * @var FrontendControllerHelper
     */
    private $controllerHelper;
    /**
     * @var AnalyticsService
     */
    private $analyticsService;

    /**
     * AdminRequestListener constructor.
     * @param FrontendControllerHelper $controllerHelper
     * @param AnalyticsService $analyticsService
     */
    public function __construct(FrontendControllerHelper $controllerHelper, AnalyticsService $analyticsService)
    {
        $this->controllerHelper = $controllerHelper;
        $this->analyticsService = $analyticsService;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $uri     = $request->getPathInfo();

        if (!$this->checkIfIsAdmin($uri)) {
            return;
        }

        $client = $this->getLoggedClient();

        if (!$client) {
            return;
        }

        $this->analyticsService->handler($request, []);
    }

    private function checkIfIsAdmin($route)
    {
        if (strpos($route, 'admin') !== false) {
            return true;
        }
        return false;
    }
}
