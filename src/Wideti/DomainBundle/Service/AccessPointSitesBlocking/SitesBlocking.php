<?php

namespace Wideti\DomainBundle\Service\AccessPointSitesBlocking;

use Wideti\DomainBundle\Entity\AccessPoints;

interface SitesBlocking
{
    public function report(AccessPoints $accessPoint);
}
