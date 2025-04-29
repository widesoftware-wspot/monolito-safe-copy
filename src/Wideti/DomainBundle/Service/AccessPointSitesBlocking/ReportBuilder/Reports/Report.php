<?php

namespace Wideti\DomainBundle\Service\AccessPointSitesBlocking\ReportBuilder\Reports;

use Wideti\DomainBundle\Entity\AccessPoints;

interface Report
{
    public function process(AccessPoints $accessPoint);
}
