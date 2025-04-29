<?php

namespace Wideti\DomainBundle\Service\AccessPointSitesBlocking\ReportBuilder;

use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Service\AccessPointSitesBlocking\ReportBuilder\Reports\Report;

interface Handler
{
    public function process(AccessPoints $accessPoint);
    public function addReport(Report $nextReport);
}
