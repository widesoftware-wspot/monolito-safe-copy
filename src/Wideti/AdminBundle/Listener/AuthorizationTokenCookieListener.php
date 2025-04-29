<?php


namespace Wideti\AdminBundle\Listener;


use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Router;
use Wideti\DomainBundle\Service\Auth\AuthorizationToken\AuthorizationTokenInterface;

class AuthorizationTokenCookieListener
{
    /**
     * @var AuthorizationTokenInterface
     */
    private $authorizationToken;
    /**
     * @var Router
     */
    private $router;

    public function __construct(AuthorizationTokenInterface $authorizationToken, Router $router)
    {
        $this->authorizationToken = $authorizationToken;
        $this->router             = $router;
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $request = $event->getRequest();
        $uri = $request->getPathInfo();

        if (!$this->isLogoutUrl($uri) && preg_match("/^\/admin/i", $uri)){
            $this->authorizationToken->saveOnCookie($request, $response);
        }
    }

    private function isLogoutUrl($uri)
    {
        $route = $this->router->match($uri)['_route'];

        return in_array($route, array("exit", "logout_admin", "spots_manager_logout"));
    }
}