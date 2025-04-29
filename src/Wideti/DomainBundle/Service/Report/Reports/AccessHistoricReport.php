<?php

namespace Wideti\DomainBundle\Service\Report\Reports;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Dto\ReportDto;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\Auditor;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\DomainBundle\Service\RadacctReport\RadacctReportServiceAware;
use Wideti\DomainBundle\Service\Report\ReportFormat;
use Wideti\DomainBundle\Service\Report\ReportType;
use Wideti\DomainBundle\Service\ReportFileBuilder\ReportFileBuilder;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;

ini_set('memory_limit', '3072M');

class AccessHistoricReport implements Report
{
    use MongoAware;
    use EntityManagerAware;
    use SessionAware;
    use LoggerAware;
    use RadacctReportServiceAware;
    use CustomFieldsAware;

    private $bucket;

    /**
     * @var FileUpload
     */
    private $fileUpload;
    /**
     * @var string
     */
    private $tempFileFolder;
    private $charset;

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var Auditor
     */
    private $auditor;

    /**
     * AccessHistoricReport constructor.
     * @param $bucket
     * @param FileUpload $fileUpload
     * @param $tempFileFolder
     * @param ConfigurationService $configurationService
     * @param Auditor $auditor
     */
    public function __construct(
        $bucket,
        FileUpload $fileUpload,
        $tempFileFolder,
        ConfigurationService $configurationService,
        Auditor $auditor
    ) {
        $this->bucket = $bucket;
        $this->fileUpload = $fileUpload;
        $this->tempFileFolder = $tempFileFolder;
        $this->configurationService = $configurationService;
        $this->auditor = $auditor;
    }

    public function countResult(array $filters, Client $client)
    {
        $dateFrom   = date_format(new \DateTime("NOW -30 days"), 'Y-m-d 00:00:00');
        $dateTo     = date_format(new \DateTime("NOW"), 'Y-m-d H:i:s');

        if (array_key_exists('date_from', $filters)) {
            $dateFrom = date('Y-m-d 00:00:00', strtotime(str_replace('/', '-', $filters['date_from'])));
        }

        if (array_key_exists('date_from', $filters)) {
            $dateTo = date('Y-m-d 23:59:59', strtotime(str_replace('/', '-', $filters['date_to'])));
        }

        $period = [
            'from' => $dateFrom,
            'to'   => $dateTo
        ];

        $queryFilter = $this->search($filters, $dateFrom, $dateTo, $client);

        $filters = [
            'maxReportLinesPoc' => $filters['maxReportLinesPoc'],
            'filters'           => $queryFilter,
            'skip'              => 0
        ];

        $count = $this->radacctReportService->countByQuery($filters, $period);

        return $count;
    }

    public function search($filters, $dateFrom, $dateTo, $client)
    {
        if (array_key_exists('filter', $filters)) {
            if ($filters['filter'] == 'calledstation_name' && $filters['access_point'] != null) {
                $ap = $this->em
                    ->getRepository('DomainBundle:AccessPoints')
                    ->getManyAccessPointById($filters['access_point'])
                ;

                $queryFilter[] = [
                    "term" => [
                        $filters['filter'] => $ap[0]["friendlyName"]
                    ]
                ];
            }

            if ($filters['value'] != null) {
                if ($filters['filter'] != "email") {
                    $queryFilter[] = [
                        "term" => [
                            $filters['filter'] => $filters['value']
                        ]
                    ];
                } else {
                    $guest = $this->mongo
                        ->getRepository('DomainBundle:Guest\Guest')
                        ->findLikeEmail($filters['value']);

                    if ($guest) {
                        $queryFilter[] = [
                            "term" => [
                                'username' => $guest->getMysql()
                            ]
                        ];
                    }
                }
            }
        }

        $queryFilter[] = [
            "query" => [
                "filtered" => [
                    "filter" => [
                        "and" => [
                            "filters" => [
                                [
                                    "term" => [ "client_id" => $client->getId() ]
                                ]
                            ],
                            "bool" => [
                                "must" => [
                                    [
                                        "exists" => [
                                            "field" => "acctstoptime"
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $range = [
            "range" => [
                "acctstarttime" => [
                    "gte" => $dateFrom,
                    "lte" => $dateTo
                ]
            ]
        ];

        $queryFilter[] = $range;

        return $queryFilter;
    }

    /**
     * @param $charset
     * @param array $filters
     * @param Client $client
     * @param Users $user
     * @param bool $isBatch
     * @param string $format
     * @return mixed|ReportDto
     * @throws \Wideti\DomainBundle\Service\AuditLogs\AuditException
     */
    public function getReport($charset, array $filters, Client $client, Users $user,$isBatch = false, $format = ReportFormat::CSV)
    {
        $this->charset = $charset;

        $content    = [];
        $columns    = [];

        $params = [
            'filters' => $filters
        ];

        $domain = $client->getDomain();

        if($client->isWhiteLabel()) {
            $domain = StringHelper::slugDomain($domain);
        }

        $this->setDefaultDatabaseOnMongo($domain);

        $fields = $this->customFieldsService->getCustomFields();

        $columns[]  = 'Visitante mac address';
        foreach ($fields as $field) {
            $columns[] = $field->getNameByLocale('pt_br');
        }
        $columns[]  = 'Início';
        $columns[]  = 'Fim';
        $columns[]  = 'IP';
        $columns[]  = 'Ponto de acesso';
        $columns[]  = 'Ponto de acesso mac address';
        $columns[]  = 'Download (bytes)';
        $columns[]  = 'Upload (bytes)';

        $fileBuilder    = null;
        $reportBatchDto = null;

        if ($isBatch) {
            $fileBuilder = new ReportFileBuilder($this->fileUpload, $this->tempFileFolder, $format);
            $reportBatchDto = new ReportDto();
            $reportBatchDto->setColumns($columns);
            $fileBuilder->addContent($reportBatchDto);
            $reportBatchDto->clearColumns();
        }

        $dateFrom   = date_format(new \DateTime("NOW -30 days"), 'Y-m-d 00:00:00');
        $dateTo     = date_format(new \DateTime("NOW"), 'Y-m-d H:i:s');

        if (array_key_exists('date_from', $params['filters'])) {
            $dateFrom = date('Y-m-d 00:00:00', strtotime(str_replace('/', '-', $params['filters']['date_from'])));
        }

        if (array_key_exists('date_from', $params['filters'])) {
            $dateTo = date('Y-m-d 23:59:59', strtotime(str_replace('/', '-', $params['filters']['date_to'])));
        }

        $period = [
            'from' => $dateFrom,
            'to'   => $dateTo
        ];

        $queryFilter = $this->search($params['filters'], $dateFrom, $dateTo, $client);

        $filters = [
            'maxReportLinesPoc' => array_key_exists('maxReportLinesPoc', $filters) ? $filters['maxReportLinesPoc'] : null,
            'filters'           => $queryFilter,
            'skip'              => 0
        ];

        $count = $this->radacctReportService->countByQuery($filters, $period);
        $i     = ceil($count / 1000);

        $guestIdsAudited = [];
        for ($page = 1; $page<=$i; $page++) {
            $iterableResult = $this->radacctReportService->findAccountingByFilter($filters, $period, $page, 1000);

            foreach ($iterableResult as $entity) {
                $guestIdsAudited[] = $entity['username'];

                $guest = $this->mongo
                    ->getRepository('DomainBundle:Guest\Guest')
                    ->findOneBy([
                        'mysql' => $entity['username']
                    ])
                ;
                if (is_null($guest)) continue;
                $guestProperties = $guest->getProperties();
                $row = [];

                array_push($row, $entity['callingstationid']);

                foreach ($fields as $field) {
                    $customPropertie = array_key_exists($field->getIdentifier(), $guestProperties)
                        ? ($guestProperties[$field->getIdentifier()]) ?: 'Não informado'
                        : 'Não informado';

                    if ($field->getType() == 'date' && $customPropertie != 'Não informado') {
                        $customPropertie = date('d/m/Y', $customPropertie->sec);
                    }
                    array_push($row, $customPropertie);
                }

                array_push($row, $entity['acctstarttime']);
                array_push($row, $entity['acctstoptime']);
                array_push($row, $entity['framedipaddress']);
                array_push($row, $this->utf8Fix($entity['calledstation_name']));
                array_push($row, $entity['calledstationid']);

                if (!isset($configMapSave[$client->getId()][$entity['calledstationid']])) {
                    $configMap = $this->configurationService->getByIdentifierOrDefault($entity['calledstationid'], $client);
                    $configMapSave[$client->getId()][$entity['calledstationid']] = ["router_mode" => $configMap["router_mode"]];
                }

                $configMap = $configMapSave[$client->getId()][$entity['calledstationid']];

                array_push($row, $entity['download']);
                array_push($row, $entity['upload']);

                array_push($content, $row);

                if ($isBatch) {
                    try {
                        if (end($iterableResult)) {
                            $reportBatchDto->setContent($content);
                            $fileBuilder->addContent($reportBatchDto);
                            $reportBatchDto->clearContent();
                            unset($content);
                            $content = [];
                        }
                    } catch (\Exception $e) {
                        $this->logger->addCritical(
                            "Erro ao criar o batch de relatorio access_historic: " . $e->getMessage()
                        );
                    }
                }
            }

            unset($iterableResult);
            $this->em->clear();
            $this->mongo->clear();
        }

        // Auditoria
        $uniqueGuestIds = array_unique($guestIdsAudited);
        foreach ($uniqueGuestIds as $id) {
            $event = $this->auditor
                ->newEvent()
                ->withClient($client->getId())
                ->withSource(Kinds::userAdmin(), $user->getId())
                ->onTarget(Kinds::guest(), $id)
                ->withType(Events::export())
                ->addDescription(AuditEvent::PT_BR, 'Usuário exportou visitante no histórico de acesso')
                ->addDescription(AuditEvent::EN_US, 'User exported visitor in access history')
                ->addDescription(AuditEvent::ES_ES, 'Visitante exportado por el usuario en el historial de acceso');
            $this->auditor->push($event);
        }


        $report = new ReportDto();

        if ($isBatch) {
            $finalFile = $fileBuilder->build();
            $result = $this->fileUpload->uploadReports(
                $this->bucket,
                $client,
                ReportType::ACCESS_HISTORIC,
                $finalFile,
                $format
            );
            unlink($finalFile);
            $report->setExpireDate($result['delete_date']);
            $report->setFilePath($result['ObjectURL']);
            $report->setIsBatch(true);
            return $report;
        } else {
            $report->setColumns($columns);
            $report->setContent($content);
        }

        return $report;
    }

    private function setDefaultDatabaseOnMongo($domain)
    {
        $manager = $this->mongo;

        $manager
            ->getConfiguration()
            ->setDefaultDB($domain)
        ;

        $this->mongo->create(
            $manager->getConnection(),
            $manager->getConfiguration(),
            $manager->getEventManager()
        );
    }

    public function utf8Fix($string)
    {
        if ($this->charset == 'windows' && $_REQUEST['fileFormat'] == ReportFormat::CSV) {
            return mb_convert_encoding($string, 'Windows-1252', 'UTF-8');
        }

        return $string;
    }
}
