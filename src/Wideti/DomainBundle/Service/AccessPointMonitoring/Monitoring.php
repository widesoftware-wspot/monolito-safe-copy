<?php

namespace Wideti\DomainBundle\Service\AccessPointMonitoring;

use Wideti\DomainBundle\Entity\AccessPoints;

interface Monitoring
{
    public function getDashboard(AccessPoints $accessPoint);
}
