<?php
namespace Wideti\AdminBundle\Listener;

use Doctrine\DBAL\Events;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Service\Auth\AuthorizationToken\AuthorizationTokenInterface;
use Wideti\DomainBundle\Service\EntityLogger\EntityLoggerService;

/**
 * Custom login listener.
 */
class LoginListener
{
    /** @var \Symfony\Component\Security\Core\SecurityContext */
    private $tokenStorage;

    /**
     * @var AuthorizationChecker
     */
    private $authorizationChecker;

    /** @var \Doctrine\ORM\EntityManager */
    private $em;

    /**
     * @var AuthorizationTokenInterface
     */
    private $authorizationToken;

    private $redisService;

    public function __construct(
        TokenStorage $tokenStorage, Doctrine $doctrine, AuthorizationTokenInterface $authorizationToken, $redisService)
    {
        $this->redisService    = $redisService;
        $this->tokenStorage    = $tokenStorage;
        $this->em              = $doctrine->getManager();
        $this->authorizationToken    = $authorizationToken;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {

            if (!$event->getAuthenticationToken()->getUser() instanceof Users) {
                return true;
            }
            /**
             * @var Users $user
             */
            $user = $event->getAuthenticationToken()->getUser();
            $user->setUltimoAcesso(new \DateTime());

            $listeners = $this->em
                ->getEventManager()
                ->getListeners(\Doctrine\ORM\Events::onFlush)
            ;

            foreach ($listeners as $listener) {
                if ($listener instanceof EntityLoggerService) {
                    $this->em->getEventManager()
                        ->removeEventListener(
                            [\Doctrine\ORM\Events::onFlush],
                            $listener
                        )
                    ;
                }
            }

            if ($this->redisService->isActive()) {
                $redisAttemptsKey = 'login_attempts_' . $user->getUsername();
                $this->redisService->remove($redisAttemptsKey);
            }

            $user = $this->authorizationToken->create($user);
            $userAuthToken = $user->getUserTokenAuth();
            $this->authorizationToken->saveOnSession($userAuthToken->getToken());

            $this->em->merge($user);
            $this->em->flush();

            return true;
        }

    }

    public function setAuthorizationChecker(AuthorizationChecker $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }
}
