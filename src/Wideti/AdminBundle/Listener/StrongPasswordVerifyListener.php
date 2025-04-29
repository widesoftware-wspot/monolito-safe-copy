<?php

namespace Wideti\AdminBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Service\Analytics\AnalyticsService;
use Wideti\WebFrameworkBundle\Aware\RouterAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class StrongPasswordVerifyListener
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

    private $securityContext;
    /**
     * AdminRequestListener constructor.
     * @param FrontendControllerHelper $controllerHelper
     * @param AnalyticsService $analyticsService
     */
    public function __construct(FrontendControllerHelper $controllerHelper, AnalyticsService $analyticsService, TokenStorage $tokenStorage)
    {
        $this->controllerHelper = $controllerHelper;
        $this->analyticsService = $analyticsService;
        $this->securityContext = $tokenStorage;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($this->isAbleToResetPassword($event)) {
            $event->setResponse($this->controllerHelper->redirectToRoute("reseted_to_strong_password"));
        }
    }

    private function isAbleToResetPassword($event) {
        return !is_null($this->getUser()) &&
            $event->getRequest()->getPathInfo() != "/admin/usuarios/reseted_to_strong_password" &&
            strpos($event->getRequest()->getPathInfo(), "/admin/first-config/") === false &&
            strpos($event->getRequest()->getPathInfo(), "/api/") === false &&
            strpos($event->getRequest()->getPathInfo(), "/twoFactorAuthentication") === false &&
            !$this->getUser()->getResetedToStrongPassword();
    }

    public function getUser()
    {
        if (null === $token = $this->securityContext->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }


    private function checkIfIsAdmin($route)
    {
        if (strpos($route, 'admin') !== false) {
            return true;
        }
        return false;
    }
}
