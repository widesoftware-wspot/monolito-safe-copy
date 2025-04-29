<?php

namespace Wideti\DomainBundle\Service\AccessPointSitesBlocking\ReportBuilder;

use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Service\AccessPointSitesBlocking\ReportBuilder\Reports\Report;

class ReportHandler implements Handler
{
    private $reports;

    public function __construct()
    {
        $this->reports = [];
    }

    public function process(AccessPoints $accessPoint)
    {
        $response = [];
        foreach ($this->reports as $report) {
            $result = $report->process($accessPoint);
            $response[$result->getReport()] = $result->getData();
        }
        return $response;
    }

    public function addReport(Report $nextReport)
    {
        $this->reports[] = $nextReport;
    }
}
