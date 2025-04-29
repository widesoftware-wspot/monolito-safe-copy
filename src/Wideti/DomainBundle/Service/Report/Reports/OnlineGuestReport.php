<?php

namespace Wideti\DomainBundle\Service\Report\Reports;

use Carbon\Carbon;
use Wideti\DomainBundle\Dto\ReportDto;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\AuditException;
use Wideti\DomainBundle\Service\AuditLogs\Auditor;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\DomainBundle\Service\RadacctReport\RadacctReportServiceAware;
use Wideti\DomainBundle\Service\Report\ReportFormat;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;

class OnlineGuestReport implements Report
{
    use RadacctReportServiceAware;
    use EntityManagerAware;
    use CustomFieldsAware;

    private $charset;

    /**
     * @var Auditor
     */
    private $auditor;

    public function __construct(
      Auditor $auditor
    ) {
        $this->auditor = $auditor;
    }

    /**
     * @param $charset
     * @param array $filters
     * @param Client $client
     * @param Users $user
     * @param bool $isBatch
     * @param string $format
     * @return mixed|ReportDto
     * @throws AuditException
     */
    public function getReport(
        $charset,
        array $filters,
        Client $client,
        Users $user,
        $isBatch = false,
        $format = ReportFormat::CSV)
    {
        $this->charset = $charset;

        $filters    = $this->getApFriendlyName($filters);
        $guests     = $this->radacctReportService->getOnlineGuests($client, $filters);
        $columns    = [];

        $fields     = $this->customFieldsService->getCustomFields();

        foreach ($fields as $field) {
            $columns[] = $field->getNameByLocale('pt_br');
        }

        array_push(
            $columns,
            'Inicio',
            'IP',
            'Ponto de Acesso',
            'Tempo de Conexão'
        );

        $content = [];

        foreach ($guests as $guest) {
            // Audit exportation
            $event = $this->auditor
                ->newEvent()
                ->withClient($client->getId())
                ->withSource(Kinds::userAdmin(), $user->getId())
                ->onTarget(Kinds::guest(), $guest['guest_mysql'])
                ->withType(Events::export())
                ->addDescription(AuditEvent::PT_BR, 'Usuário exportou visitante na listagem de visitantes online')
                ->addDescription(AuditEvent::EN_US, 'User exported visitor in the online visitor list')
                ->addDescription(AuditEvent::ES_ES, 'Visitante exportado por el usuario en la lista de visitantes en línea');
            $this->auditor->push($event);

            $result = [];

            foreach ($fields as $field) {
                $customPropertie = array_key_exists($field->getIdentifier(), $guest['guest_properties'])
                    ? $guest['guest_properties'][$field->getIdentifier()]
                    : 'Não informado';

                if ($field->getType() == 'date' && $customPropertie != 'Não informado') {
                    $customPropertie = date('d/m/Y', $customPropertie->sec);
                }
                $result["guest_{$field->getIdentifier()}"] =  $customPropertie;
            }

            $accStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $guest['acctstarttime']);
            $dateNow = Carbon::now('America/Sao_Paulo');
            $result['acctstarttime']                   = $accStartTime->format('d/m/Y H:i:s');
            $result['framedipaddress']                 = $guest['framedipaddress'];
            $result['calledstation_name']              = $this->utf8Fix($guest['calledstation_name']);
            $result['conection_time']                  = $dateNow->diff($accStartTime)->format('%H:%I:%S');
            array_push($content, $result);
        }
        $reportDto = new ReportDto();
        $reportDto->setColumns($columns);
        $reportDto->setContent($content);

        return $reportDto;
    }

    public function countResult(array $filters, Client $client)
    {
        $filters = $this->getApFriendlyName($filters);
        return $this->radacctReportService->totalOnlineGuests($client, $filters);
    }

    /**
     * @param array $filters
     * @return array
     */
    private function getApFriendlyName(array $filters)
    {
        if (isset($filters['filters']['access_point']) && !empty($filters['filters']['access_point'])) {
            $ap = $this->em->getRepository('DomainBundle:AccessPoints')
                ->findOneBy([
                    'id' => $filters['filters']['access_point']
                ]);

            $filters['filters']['access_point'] = !empty($ap) ? $ap->getFriendlyName() : "";
            return $filters;
        }
        return $filters;
    }

    public function utf8Fix($string)
    {
        if ($this->charset == 'windows' && $_REQUEST['fileFormat'] == ReportFormat::CSV) {
            return mb_convert_encoding($string, 'Windows-1252', 'UTF-8');
        }

        return $string;
    }
}
