<?php
namespace Wideti\AdminBundle\Listener;

use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Wideti\DomainBundle\Entity\Users;
use Wideti\AdminBundle\Helpers\TwoFactorAuthentication;

/**
 *  Listen for successful authentication. It checks if the user supports two-factor authentication.
 *  If that’s the case the session attribute will be added and the authentication code will be sent to 
 *  the user’s email address.
 */
class TwoFactorAuthenticationCheck
{
    
    private $helper;

    /**
     * Construct a listener, which is handling successful authentication
     * @var TwoFactorAuthentication $helper
     */
    public function __construct(TwoFactorAuthentication $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Listen for successful login events
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        if (!$event->getAuthenticationToken() instanceof UsernamePasswordToken)
        {
            return;
        }

        //Check if user can do two-factor authentication
        $token = $event->getAuthenticationToken();
        $user = $token->getUser();
        $session = $event->getRequest()->getSession();
        if (!$user instanceof Users)
        {
            return;
        }
        
        if (!$user->hasTwoFactorAuthenticationEnabled())
        {
            return;
        }

        //Set flag in the session
        $event->getRequest()->getSession()->set($this->helper->getSessionKey($session), null);

    }

}
