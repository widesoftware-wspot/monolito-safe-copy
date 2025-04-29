<?php

namespace Wideti\DomainBundle\Repository\Elasticsearch\Report;

/**
 *
 * Usage: - [ setReportRepository, ["@core.repository.elasticsearch.report"] ]
 */
trait ReportRepositoryAware
{
    /**
     * @var ReportRepository
     */
    protected $reportRepository;

    public function setReportRepository(ReportRepository $reportRepository)
    {
        $this->reportRepository = $reportRepository;
    }
}
