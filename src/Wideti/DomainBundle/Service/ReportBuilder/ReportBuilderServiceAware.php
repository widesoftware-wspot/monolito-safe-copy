<?php
namespace Wideti\DomainBundle\Service\ReportBuilder;


trait ReportBuilderServiceAware
{
    /**
     * @var ReportBuilderService
     */
    protected $reportBuilder;

    public function setReportBuilder(ReportBuilderService $service)
    {
        $this->reportBuilder = $service;
    }
}
