<?php

namespace Wideti\AdminBundle\Security\User;

use Monolog\Logger;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Wideti\AdminBundle\Security\IDP\Exception\FailToAddRoleToUserException;
use Wideti\AdminBundle\Security\IDP\Exception\FailToCreateUserException;
use Wideti\AdminBundle\Security\IDP\Exception\UserNotFoundException;
use Wideti\AdminBundle\Security\IDP\Management\Management;
use Wideti\AdminBundle\Security\IDP\UserAuthentication;
use Wideti\DomainBundle\Entity\Roles;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Service\User\UserService;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class WspotUserPosAuthenticator implements AuthenticationSuccessHandlerInterface
{
    /**
     * @var UserService
     */
    private $userService;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var Router
     */
    private $router;
    /**
     * @var Logger
     */
    private $logger;

    use SecurityAware;

    /**
     * WspotUserPreAuthenticator constructor.
     * @param UserService $userService
     * @param Session $session
     * @param Router $router
     * @param Logger $logger
     */
    public function __construct(
        UserService $userService,
        Session $session,
        Router $router,
        Logger $logger
    ) {
        $this->userService = $userService;
        $this->session = $session;
        $this->router = $router;
        $this->logger = $logger;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
		/**
		 * @var Users $user
		 */
    	$user = $this->getUser();
    	if ($user->isSpotManager()) {
			return new RedirectResponse($this->router->generate('spots_manager_index'));
		}

        return new RedirectResponse($this->router->generate('admin_dashboard'));
    }
}
