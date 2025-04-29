<?php

namespace Wideti\DomainBundle\Twig;

use Wideti\DomainBundle\Entity\AccessPoints;

class AccessPointStatus extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('access_point_status', array($this, 'getAccessPointStatus')),
        );
    }

    public function getAccessPointStatus($status)
    {
        $guest = new AccessPoints();
        $guest->setStatus($status);

        return $guest->getStatusAsString();
    }

    public function getName()
    {
        return 'access_point_status';
    }
}
