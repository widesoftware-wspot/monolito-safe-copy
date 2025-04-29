<?php

namespace Wideti\DomainBundle\Twig;

use Wideti\DomainBundle\Document\Guest\Guest;

class GuestStatus extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('guest_status', array($this, 'getGuestStatus')),
        );
    }

    public function getGuestStatus($status)
    {
        $guest = new Guest();
        $guest->setStatus($status);

        return $guest->getStatusAsString();
    }

    public function getName()
    {
        return 'guest_status';
    }
}
