<?php
namespace Wideti\AdminBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Wideti\AdminBundle\Helpers\TwoFactorAuthentication;

/**
 * It checks if the user is still flagged with “authentication not complete”, then a form is displayed.
 * When the request contains an authenticaton code, it is validated. If it’s wrong, 
 * a flash message is set and the form is displayed again. 
 * If it’s corrent the flag in the session is updated and the user will be forwarded to the dashboard.
 */
class TwoFactorAuthenticationValidator
{
    /**
     * @var TwoFactorAuthentication $helper
     */
    protected $helper;

    /**
     * @var TokenStorage  $securityContext
     */
    protected $securityContext;

    /**
     * @var EngineInterface $templating
     */
    protected $templating;

    /**
     * @var Router $router
     */
    protected $router;

    /**
     * Construct the listener
     * @param TwoFactor $helper
     * @param TokenStorage`  $securityContext
     * @param EngineInterface $templating
     * @param Router $router
     */
    public function __construct(TwoFactorAuthentication $helper, TokenStorage $securityContext, EngineInterface $templating, Router $router)
    {
        $this->helper = $helper;
        $this->securityContext = $securityContext;
        $this->templating = $templating;
        $this->router = $router;
    }

    /**
     * Listen for request events
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $token = $this->securityContext->getToken();
        if (!$token)
        {
            return;
        }
        if (!$token instanceof UsernamePasswordToken)
        {
            return;
        }

		$session = $event->getRequest()->getSession();
        $key = $this->helper->getSessionKey($session);
        $request = $event->getRequest();

        $uri = $request->getUri();
        if (preg_match('/\/admin\/login\/twoFactorAuthentication/i', $uri)) {
        	return;
		}

        if (preg_match('/\/panel\/login\/twoFactorAuthentication/i', $uri)) {
        	return;
		}

        //Check if user has to do two-factor authentication
        if (!$session->has($key))
        {
            return;
        }

        if ($session->get($key) === true)
        {
            return;
        }

        if ($session->get("panel_access") === "manager") {
			$route = $this->router->generate("login_panel_2fa");
			$event->setResponse(new RedirectResponse($route));
			return;
		}

		$route = $this->router->generate("login_admin_2fa");
		$event->setResponse(new RedirectResponse($route));

    }
}