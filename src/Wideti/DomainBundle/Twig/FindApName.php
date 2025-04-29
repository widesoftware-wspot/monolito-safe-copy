<?php

namespace Wideti\DomainBundle\Twig;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class FindApName extends \Twig_Extension
{
    use EntityManagerAware;
    use SessionAware;

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('find_ap_name', array($this, 'getApName')),
        );
    }

    public function getApName($macAddress)
    {
        $client = $this->getLoggedClient();

        $accessPoint = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->getAccessPoint($macAddress, $client->getId())
        ;

        if ($accessPoint) {
            return $accessPoint->getFriendlyName();
        }

        return $macAddress;
    }

    public function getName()
    {
        return 'find_ap_name';
    }
}
