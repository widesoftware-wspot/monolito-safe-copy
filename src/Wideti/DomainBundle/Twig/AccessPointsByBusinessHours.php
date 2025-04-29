<?php

namespace Wideti\DomainBundle\Twig;

use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;

class AccessPointsByBusinessHours extends \Twig_Extension
{
    use EntityManagerAware;
    use MongoAware;

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('aps_by_business_hours', array($this, 'getAccessPoints')),
        );
    }

    public function getAccessPoints($accessPoints)
    {
        $aps = [];

        foreach ($accessPoints as $data) {
            array_push($aps, $data->getId());
        }

        $entities = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->getAccessPointsById($aps);

        $accessPoints = '';

        foreach ($entities as $entity) {
            $accessPoints .= $entity->getFriendlyName() . ' - ';
        }

        return substr($accessPoints, 0, -3);
    }

    public function getName()
    {
        return 'aps_by_business_hours';
    }
}
