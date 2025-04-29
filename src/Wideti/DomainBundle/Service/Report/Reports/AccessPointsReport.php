<?php

namespace Wideti\DomainBundle\Service\Report\Reports;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Dto\ReportDto;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Service\RadacctReport\RadacctReportServiceAware;
use Wideti\DomainBundle\Service\Report\ReportFormat;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;


class AccessPointsReport implements Report
{
    use MongoAware;
    use EntityManagerAware;
    use RadacctReportServiceAware;

    /**
     * @var FileUpload
     */
    private $fileUpload;
    /**
     * @var string
     */
    private $tempFileFolder;
    private $charset;

    public function __construct(FileUpload $fileUpload, $tempFileFolder)
    {
        $this->fileUpload = $fileUpload;
        $this->tempFileFolder = $tempFileFolder;
    }

    public function countResult(array $filters, Client $client)
    {
        return true;
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
        $content    = [];
        $columns    = [];
        $columns[]  = 'Ponto de Acesso';
        $columns[]  = 'Total de Visitas';
        $columns[]  = 'Total de Cadastros';

        if (array_key_exists('date_from', $filters)) {
            $date_from = date('Y-m-d', strtotime(str_replace('/', '-', $filters['date_from'])));
        } else {
            $date_from  = new \DateTime("NOW -30 days");
            $date_from  = $date_from->format('Y-m-d');
        }

        if (array_key_exists('date_to', $filters)) {
            $date_to = date('Y-m-d', strtotime(str_replace('/', '-', $filters['date_to'])));
        } else {
            $date_to    = new \DateTime("NOW");
            $date_to    = $date_to->format('Y-m-d');
        }
        $access_point = (array_key_exists('access_point', $filters) ? $filters['access_point'] : []);
        
        
        if ($access_point) {
            $accessPoints = $this->em
                ->getRepository('DomainBundle:AccessPoints')
                ->getManyAccessPointById($access_point);
            $numberOfAps = 0;
            $access_point = [];
            foreach ($accessPoints as $aps) {
                $numberOfAps += 1;
                array_push(
                    $access_point, [ "term" => [ "friendlyName" => $aps['friendlyName'] ] ]
                );
            }
        } else {
            $numberOfAps = 10;
        }
        $period = [
            'date_from'  => $date_from,
            'date_to'    => $date_to
        ];
        
        $result = $this->radacctReportService->getVisitsAndRecordsPerAccessPoint(
            $client,
            $period,
            $access_point,
            $numberOfAps
        );
        foreach ($result as $data) {
            $row = [];
            array_push($row, $data['key']);
            array_push($row, $data['totalVisits']['value']);
            array_push($row, $data['totalRegistrations']['value']);
            array_push($content, $row);
        }

        $report = new ReportDto();

        $report->setColumns($columns);
        $report->setContent($content);

        return $report;
    }

    public function utf8Fix($string)
    {
        if ($this->charset == 'windows' && $_REQUEST['fileFormat'] == ReportFormat::CSV) {
            return mb_convert_encoding($string, 'Windows-1252', 'UTF-8');
        }

        return $string;
    }
}
