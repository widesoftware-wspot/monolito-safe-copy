<?php

namespace Wideti\DomainBundle\Service\Report\Reports;

use Wideti\DomainBundle\Dto\ReportDto;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Service\Report\ReportFormat;

interface Report
{
    /**
     * @param $charset
     * @param array $filters
     * @param Client $client
     * @param bool $isBatch
     * @param string $format
     * @param Users $user
     * @return mixed
     */
    public function getReport(
        $charset,
        array $filters,
        Client $client,
        Users $user,
        $isBatch = false,
        $format = ReportFormat::CSV
    );
    public function countResult(array $filters, Client $client);
}
