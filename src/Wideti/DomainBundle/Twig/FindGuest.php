<?php

namespace Wideti\DomainBundle\Twig;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\WebFrameworkBundle\Aware\MongoAware;

class FindGuest extends \Twig_Extension
{
    use MongoAware;

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('find_guest', array($this, 'findGuestInMongo')),
        );
    }

    public function findGuestInMongo($idMysql)
    {
        $guest = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneByMysql((int)$idMysql);

        return $guest;
    }

    public function getName()
    {
        return 'find_guest';
    }
}
