<?php

namespace Wideti\DomainBundle\Service\Report\Reports;

use Wideti\DomainBundle\Dto\ReportDto;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Service\Report\ReportFormat;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;

class CampaignReport implements Report
{
    use EntityManagerAware;

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
     * @return mixed|ReportDto
     * @throws \Exception
     */
    public function getReport($charset, array $filters, Client $client, Users $user,$isBatch = false, $format = ReportFormat::CSV)
    {
        $this->charset = $charset;

        $content = [];

        $columns = array();
        $columns[] = 'Campanha';
        $columns[] = 'Total de visualizações';
        $columns[] = 'Pré Login';
        $columns[] = 'Pós Login';

        $campaign       = (array_key_exists('campaign', $filters)) ? $filters['campaign'] : null;
        $dateFrom       = (array_key_exists('date_from', $filters)) ?
            new \DateTime(str_replace('/', '-', $filters['date_from'])) : null;
        $dateTo         = (array_key_exists('date_to', $filters)) ?
            new \DateTime(str_replace('/', '-', $filters['date_to'])) : null;

        $campaignViews = $this->em
            ->getRepository('DomainBundle:CampaignViews')
            ->getMostViewedHours($client->getId(), $campaign, $dateFrom, $dateTo);

        foreach ($campaignViews as $campaign) {
            $row = [];
            array_push($row, $this->utf8Fix($campaign['name']));
            array_push($row, $campaign['total']);

            $views = $this->em
                ->getRepository("DomainBundle:CampaignViews")
                ->getMostViewedHoursByCampaign(
                    $campaign['id'],
                    $filters
                );

            foreach ($views as $view) {
                array_push($row, $view['total']);
            }

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
