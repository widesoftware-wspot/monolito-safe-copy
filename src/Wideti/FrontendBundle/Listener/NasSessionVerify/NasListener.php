<?php

namespace Wideti\FrontendBundle\Listener\NasSessionVerify;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class NasListener
{
    use SessionAware;
    use LoggerAware;

    private $emptyNasRedirectUrl;
    private $uncheckedRoutes;

    public function __construct($emptyNasRedirectUrl, $uncheckedRoutes)
    {
        $this->emptyNasRedirectUrl = $emptyNasRedirectUrl;
        $this->uncheckedRoutes = $uncheckedRoutes;
    }

    public function onRequest(FilterControllerEvent $event)
    {
        if (!$this->isControllerNasHandler($event)) {
            return;
        }

        if ($this->isUncheckedRoute($event->getRequest()->get('_route'))) {
            return;
        }

        if (!$this->nasExists()) {
            $this->sendLog($event);
            $event->setController(function () {
                return new RedirectResponse($this->emptyNasRedirectUrl);
            });
        }
    }

    /**
     * @param $routeName
     * @return bool
     */
    private function isUncheckedRoute($routeName)
    {
        return in_array($routeName, $this->uncheckedRoutes);
    }

    /**
     * @param FilterControllerEvent $event
     * @return bool
     */
    private function isControllerNasHandler(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        if (!is_array($controller)) {
            return false;
        }

        if ($controller[0] instanceof NasControllerHandler) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    private function nasExists()
    {
        return $this->session->get(Nas::NAS_SESSION_KEY) ? true : false;
    }

    private function sendLog(FilterControllerEvent $event)
    {
        $routerName = $event->getRequest()->get('_route');
        $referer = $event->getRequest()->headers->get('referer');
        $headersAsArray = $event->getRequest()->headers->all();
        $this->logger->addWarning(
            "Guest redirected to wifi check, Guest has Nas Null in route: {$routerName}, HTTP REFERER: {$referer}",
            $headersAsArray
        );
    }
}
