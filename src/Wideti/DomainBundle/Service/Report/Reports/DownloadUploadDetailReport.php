<?php

namespace Wideti\DomainBundle\Service\Report\Reports;

use Wideti\DomainBundle\Dto\ReportDto;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\ConverterHelper;
use Wideti\DomainBundle\Helpers\WifiMode;
use Wideti\DomainBundle\Repository\Elasticsearch\Report\ReportRepositoryAware;
use Wideti\DomainBundle\Service\Report\ReportFormat;

class DownloadUploadDetailReport implements Report
{
    use ReportRepositoryAware;

    private $charset;
    /**
     * @var WifiMode
     */
    private $wifiMode;

    /**
     * DownloadUploadDetailReport constructor.
     * @param WifiMode $wifiMode
     */
    public function __construct(WifiMode $wifiMode)
    {
        $this->wifiMode = $wifiMode;
    }

    /**
     * @param $charset
     * @param array $filters
     * @param Client $client
     * @param Users $user
     * @param bool $isBatch
     * @param string $format
     * @return ReportDto
     */
    public function getReport($charset, array $filters, Client $client, Users $user, $isBatch = false, $format = ReportFormat::CSV)
    {
        $this->charset = $charset;

        $columns   = ['Ano', 'Mês', 'Dia', 'Download', 'Upload'];
        $reportDto = new ReportDto();

        $reportDto->setColumns($columns);

        $details = $this->reportRepository->getDownloadUploadByDate(
            $client,
            $filters['period'],
            $filters['accessPoint'],
            'download',
            'upload',
            $filters['interval'],
            $filters['format_range'],
            $filters['format_aggs']
        );

        $results    = $details['aggregations']['download_upload']['buckets'];
        $content     = [];

        foreach ($results as $result) {
            $row = [];
            list($year, $month, $day) = explode("-", $result['key_as_string']);
            $row['year']    = $year;
            $row['month']   = ConverterHelper::getStringMonth($month);
            $row['day']     = $day;
            $row['download']= ConverterHelper::byteToGBorMB($result['download']['value']);
            $row['upload']  = ConverterHelper::byteToGBorMB($result['upload']['value']);
            $content[] = $row;
        }

        $reportDto->setContent($content);
        return $reportDto;
    }

    public function countResult(array $filters, Client $client)
    {
        return true;
    }

    public function utf8Fix($string)
    {
        if ($this->charset == 'windows' && $_REQUEST['fileFormat'] == ReportFormat::CSV) {
            return mb_convert_encoding($string, 'Windows-1252', 'UTF-8');
        }

        return $string;
    }
}
