<?php

namespace Wideti\DomainBundle\Service\Report\Reports;

use Wideti\DomainBundle\Dto\ReportDto;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\DateTimeHelper;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Service\RadacctReport\RadacctReportServiceAware;
use Wideti\DomainBundle\Service\Report\ReportFormat;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;

class MostVisitedHoursReport implements Report
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
     * @throws \Exception
     */
    public function getReport($charset, array $filters, Client $client, Users  $user, $isBatch = false, $format = ReportFormat::CSV)
    {
        $this->charset = $charset;
        $content    = [];
        $columns    = [];
        $columns[]  = $this->utf8Fix('Horário');
        $columns[]  = 'Total de Visitas';
        $columns[]  = 'Total de Cadastros';

        $fileBuilder    = null;
        $reportBatchDto = null;

        $dateFrom   = date_format(new \DateTime("NOW -30 days"), 'Y-m-d');
        $dateTo     = date_format(new \DateTime("NOW"), 'Y-m-d');

        if (array_key_exists('date_from', $filters)) {
            $dateFrom = date('Y-m-d', strtotime(str_replace('/', '-', $filters['date_from'])));
        }

        if (array_key_exists('date_from', $filters)) {
            $dateTo = date('Y-m-d', strtotime(str_replace('/', '-', $filters['date_to'])));
        }

        $dateDiff = date_diff(new \DateTime($dateFrom), new \DateTime($dateTo));

        if ($dateDiff->days > 31) {
            $dateFrom  = date_format(new \DateTime("NOW -30 days"), 'Y-m-d');
            $dateTo    = date_format(new \DateTime("NOW"), 'Y-m-d');
        }

        $access_point = (array_key_exists('access_point', $filters) ? $filters['access_point'] : []);

        if ($access_point) {
            $accessPoints = $this->em
                ->getRepository('DomainBundle:AccessPoints')
                ->getManyAccessPointById($access_point);

            $access_point = [];

            foreach ($accessPoints as $aps) {
                array_push(
                    $access_point,
                    [ "term" => [ "friendlyName" => $aps['friendlyName'] ] ]
                );
            }
        }

	    $search = $this->radacctReportService->mostAccessesHours(
            $client,
            [
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
                'filtered'  => true
            ],
            $access_point
        );

        $hours = [
            '0', '1', '2', '3', '4', '5', '6', '7',
            '8', '9', '10', '11', '12', '13', '14', '15',
            '16', '17', '18', '19', '20', '21', '22', '23'
        ];

        foreach ($hours as $hour) {
            array_push(
                $content,
                [
                    DateTimeHelper::formatHour($hour) . "h",
                    0,
                    0
                ]
            );
        }

	    foreach ($search['access_by_hour_visits']['buckets'] as $data) {
		    $key = (int) explode(':', $data['key'])[0];
		    $content[$key][1] = $data['totalVisits']['value'];

	    }

	    foreach ($search['access_by_hour_registrations']['buckets'] as $data) {
            $key = (int) explode(':', $data['key'])[0];
		    $content[$key][2] = $data['totalRegistrations']['value'];
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
