<?php
namespace Wideti\DomainBundle\Service\ReportBuilder;

use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class ReportBuilderService
{
    use SessionAware;
    use MongoAware;

    public function downloadUploadData(array $values = null, $type = null)
    {
    	if ($values['hits']['total'] == 0) return null;

        $resultset  = $values['aggregations']['download_upload']['buckets'];
        $data       = [];

        foreach ($resultset as $result) {
            if ($type == 'month') {
                $xaxis = $result['key_as_string'];
            } else {
                $xaxis = strtotime($result['key_as_string']) * 1000;
            }

            $data['upload'][] = [
                $xaxis,
                (int) $this->convertByteToGBorMB($result['upload']['value'])
            ];

            $data['download'][] = [
                $xaxis,
                (int) $this->convertByteToGBorMB($result['download']['value'])
            ];
        }

        return $data;
    }

    public function convertByteToGBorMB($bytes)
    {
        return number_format(($bytes/1024/1024), 2, '.', '').' MB';
    }

    public function formatMonth($month)
    {
        switch ($month) {
            case 1:
                $month = 'Janeiro';
                break;
            case 2:
                $month = 'Fevereiro';
                break;
            case 3:
                $month = 'Março';
                break;
            case 4:
                $month = 'Abril';
                break;
            case 5:
                $month = 'Maio';
                break;
            case 6:
                $month = 'Junho';
                break;
            case 7:
                $month = 'Julho';
                break;
            case 8:
                $month = 'Agosto';
                break;
            case 9:
                $month = 'Setembro';
                break;
            case 10:
                $month = 'Outubro';
                break;
            case 11:
                $month = 'Novembro';
                break;
            case 12:
                $month = 'Dezembro';
                break;
        }

        return $month;
    }

    public function prepareReturningGuestsData($accoutings)
    {
        $data = [];

        foreach ($accoutings as $acct) {
            $repository = $this->mongo->getRepository('DomainBundle:Guest\Guest');
            $guest = $repository->findOneBy([ "mysql" => $acct['key'] ]);

            $report = [];
            $report['total_visits']  = $acct['total_visits'];
            $report['download']      = $acct['download']['value'];
            $report['upload']        = $acct['upload']['value'];
            $report['loginField']    = $guest->getProperties()[$guest->getLoginField()];
            $report['name']          = isset($guest->getProperties()['name']) ?
                $guest->getProperties()['name'] : "Não informado";
            $report['lastAccess']    = $guest->getLastAccess();
            $report['created']       = $guest->getCreated();
            $report['guest_id']      = $guest->getId();
            $report['averageTime']   = $acct['averageTime']['value'];

            $data[] = $report;
        }

        return $data;
    }
}
