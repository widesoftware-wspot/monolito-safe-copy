<?php

namespace Wideti\DomainBundle\Service\RadacctReport;

/**
 *
 * Usage: - [ setRadacctReportService, ["@core.service.radacct_report"] ]
 */
trait RadacctReportServiceAware
{
    /**
     * @var RadacctReportService
     */
    protected $radacctReportService;

    public function setRadacctReportService(RadacctReportService $radacctReportService)
    {
        $this->radacctReportService = $radacctReportService;
    }
}
