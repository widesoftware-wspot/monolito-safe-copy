<?php

namespace Wideti\DomainBundle\Service\AccessPointSitesBlocking\ReportBuilder\Reports\Dto;

class ReportBuilder
{
    const REPORT_BLOCKED_CATEGORIES         = 'blockedCategories';
    const REPORT_MOST_ACCESSED_CATEGORIES   = 'mostAccessedCategories';
    const REPORT_BLOCKED_DOMAINS            = 'blockedDomains';
    const REPORT_MOST_ACCESSED_DOMAINS      = 'mostAccessedDomains';

    private $report;
    private $data;

    /**
     * @param $report
     * @return $this
     */
    public function withReport($report)
    {
        $this->report = $report;
        return $this;
    }

    /**
     * @param $data
     * @return $this
     */
    public function withData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function build()
    {
        $dto = new ReportDto();
        $dto->setReport($this->report);
        $dto->setData($this->data);
        return $dto;
    }
}