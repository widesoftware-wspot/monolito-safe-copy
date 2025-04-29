<?php

namespace Wideti\DomainBundle\Twig;

use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class AccessPointsByIdentifier extends \Twig_Extension
{
    use EntityManagerAware;
    use SessionAware;

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('aps_by_identifier', [$this, 'getAccessPoint'])
        ];
    }

    public function getAccessPoint($identifier)
    {
        $client = $this->session->get('wspotClient');

        if (!$identifier) {
            return 'Não informado';
        }

        $accessPoint = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->getAccessPointByIdentifier($identifier, $client);

        if (!$accessPoint) {
            return 'Não informado';
        }

        return $accessPoint[0]->getFriendlyName();
    }

    public function getName()
    {
        return 'aps_by_identifier';
    }
}
