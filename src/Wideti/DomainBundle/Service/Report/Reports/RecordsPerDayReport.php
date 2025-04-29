<?php

namespace Wideti\DomainBundle\Service\Report\Reports;

use Wideti\DomainBundle\Dto\ReportDto;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\DateTimeHelper;
use Wideti\DomainBundle\Service\RadacctReport\RadacctReportServiceAware;
use Wideti\DomainBundle\Service\Report\ReportFormat;
use Wideti\WebFrameworkBundle\Aware\MongoAware;

class RecordsPerDayReport implements Report
{
    use MongoAware;
    use RadacctReportServiceAware;

    private $charset;

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
        $this->charset  = $charset;
        $reportDto      = new ReportDto();

        $totalVisitsAndRegisters = $this->radacctReportService->processVisitsAndRecordsPerDay(
            $client,
            [
                'date_from' => $filters['date_from'],
                'date_to'   => $filters['date_to'],
                'filtered'  => true
            ],
            $filters['access_points']
        );

        $totalList  = [];
        $totalGraph = [];

        foreach ($filters['period'] as $date) {
            $totalGraph[$date->format("d/m")] = 0;
        }

	    $perDayGraph = DateTimeHelper::daysOfWeek();
	    $perDayList  = [];

	    foreach ($totalVisitsAndRegisters as $data) {
		    $totalRegisters = $data['totalRegistrations']['value'];

		    $notFormatted   = explode('/', $data['key_as_string']);
		    $dateFormatted  = date('Y') . "-{$notFormatted[1]}-{$notFormatted[0]}";
		    $dayOfWeek      = date('w', strtotime($dateFormatted));
		    $perDayGraph[$dayOfWeek] += (int) $totalRegisters;

		    if ($totalRegisters > 0) {
			    $totalList[$data['key_as_string']]['total']  = (int) $totalRegisters;
			    $totalList[$data['key_as_string']]['period'] = $dateFormatted;
		    }

		    $totalGraph[$data['key_as_string']] = (int) $totalRegisters;
	    }

	    foreach ($perDayGraph as $key => $value) {
		    $perDayList[$key] = is_int($value)
			    ? $value
			    : 0
		    ;
	    }

        $content = [];

        foreach ($totalList as $key => $value) {
            $row['day']     = $key;
            $row['total']   = $value['total'];
            $content[]      = $row;
        }

        $content[] = [' ',' '];

        if ($format == ReportFormat::PDF) {
            $content[] = [
                '<center><b>Dia</b></center>',
                '<center><b>Total de Cadastros</b></center>'
            ];
        } else {
            $content[] = [
                'Dia',
                'Total de Cadastros'
            ];
        }

        foreach ($perDayList as $key => $value) {
            $row['day']     = DateTimeHelper::getDayOfWeek($key);
            $row['total']   = $value;
            $content[]      = $row;
        }

        $reportDto->setColumns(['Dia', "Total de Cadastros"]);
        $reportDto->setContent($content);

        return $reportDto;
    }

    public function utf8Fix($string)
    {
        if ($this->charset == 'windows' && $_REQUEST['fileFormat'] == ReportFormat::CSV) {
            return mb_convert_encoding($string, 'Windows-1252', 'UTF-8');
        }

        return $string;
    }
}
