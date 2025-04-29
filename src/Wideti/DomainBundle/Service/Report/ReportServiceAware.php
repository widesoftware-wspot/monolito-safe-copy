<?php

namespace Wideti\DomainBundle\Service\Report;

/**
 *
 * Usage: - [ setReportService, [@core.service.report] ]
 */
trait ReportServiceAware
{
    /**
     * @var ReportService
     */
    protected $reportService;

    public function setReportService(ReportService $service)
    {
        $this->reportService = $service;
    }
}
