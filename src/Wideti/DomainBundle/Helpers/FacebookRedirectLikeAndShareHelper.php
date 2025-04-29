<?php

namespace Wideti\DomainBundle\Helpers;

use Symfony\Component\HttpFoundation\Session\Session;

class FacebookRedirectLikeAndShareHelper
{
    const SLUG = "facebook.share.";

    /**
     * @var Session
     */
    private $session;

    /**
     * FacebookRedirectLikeAndShareHelper constructor.
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function getFromSession($guestId)
    {
        return $this->session->get(self::SLUG . $guestId);
    }

    public function putOnSession($guestId)
    {
        $this->session->set(self::SLUG . $guestId, true);
    }

    public function removeFromSession($guestId)
    {
        $this->session->remove(self::SLUG . $guestId);
    }
}
