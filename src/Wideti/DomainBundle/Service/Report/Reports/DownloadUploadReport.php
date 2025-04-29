<?php

namespace Wideti\DomainBundle\Service\Report\Reports;

use Wideti\DomainBundle\Dto\ReportDto;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\ConverterHelper;
use Wideti\DomainBundle\Helpers\WifiMode;
use Wideti\DomainBundle\Repository\Elasticsearch\Report\ReportRepositoryAware;
use Wideti\DomainBundle\Service\Report\ReportFormat;

class DownloadUploadReport implements Report
{
    use ReportRepositoryAware;
    /**
     * @var WifiMode
     */
    private $wifiMode;
    private $charset;

    /**
     * DownloadUploadReport constructor.
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

        $columns = ["Ano", "Mês", "Download", "Upload"];

        $reportDto = new ReportDto();
        $reportDto->setColumns($columns);

        $result = $this->reportRepository->getDownloadUploadByDate(
            $client,
            $filters['period'],
            array_key_exists('access_point', $filters) ? $filters['access_point'] : null,
            'download',
            'upload',
            $filters['interval'],
            $filters['format_range'],
            $filters['format_aggs']
        );

        $result = $result['aggregations']['download_upload']['buckets'];

        $content = array_map(function ($row) {
            $content['year']     = substr($row['key_as_string'], 0, 4);
            $content['month']    = $this->utf8Fix(ConverterHelper::getStringMonth(substr($row['key_as_string'], 5, 2)));
            $content['download'] = ConverterHelper::byteToGBorMB($row['download']['value']);
            $content['upload']   = ConverterHelper::byteToGBorMB($row['upload']['value']);

            return $content;
        }, $result);

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
