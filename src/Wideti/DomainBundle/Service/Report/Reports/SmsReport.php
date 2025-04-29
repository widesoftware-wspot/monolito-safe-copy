<?php

namespace Wideti\DomainBundle\Service\Report\Reports;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Dto\ReportDto;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\LegalKinds;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Service\LegalBaseManager\LegalBaseManagerService;
use Wideti\DomainBundle\Service\RadacctReport\RadacctReportServiceAware;
use Wideti\DomainBundle\Service\Report\ReportFormat;
use Wideti\DomainBundle\Service\Report\ReportType;
use Wideti\DomainBundle\Service\ReportFileBuilder\ReportFileBuilder;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class SmsReport implements Report
{
    use MongoAware;
    use EntityManagerAware;
    use SessionAware;
    use LoggerAware;

    /**
     * @var FileUpload
     */
    private $fileUpload;
    /**
     * @var string
     */
    private $tempFileFolder;
    private $charset;
    private $bucket;

    private $legalBaseManager;

    /**
     * SmsReport constructor.
     * @param $bucket
     * @param FileUpload $fileUpload
     * @param $tempFileFolder
     */
    public function __construct($bucket, FileUpload $fileUpload, $tempFileFolder, LegalBaseManagerService $legalBaseManagerService)
    {
        $this->bucket = $bucket;
        $this->fileUpload = $fileUpload;
        $this->tempFileFolder = $tempFileFolder;
        $this->legalBaseManager = $legalBaseManagerService;
    }

    public function countResult(array $filters, Client $client)
    {
        $dateFrom   = date_format(new \DateTime("NOW -30 days"), 'Y-m-d 00:00:00');
        $dateTo     = date_format(new \DateTime("NOW"), 'Y-m-d H:i:s');

        if (array_key_exists('date_from', $filters) && !empty($filters['date_from'])) {
            $dateFrom = date('Y-m-d 00:00:00', strtotime(str_replace('/', '-', $filters['date_from'])));
            $dateFrom = new \DateTime($dateFrom);
        }

        if (array_key_exists('date_from', $filters) && !empty($filters['date_to'])) {
            $dateTo = date('Y-m-d 23:59:59', strtotime(str_replace('/', '-', $filters['date_to'])));
            $dateTo = new \DateTime($dateTo);
        }

        $filters = [
            'maxReportLinesPoc' => $filters['maxReportLinesPoc'],
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
            'skip'  => 0
        ];

        $count = $this->em
            ->getRepository('DomainBundle:SmsHistoric')
            ->reportSms($this->getLoggedClient(), null, null, $filters, true);
        ;

        return $count;
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
    public function getReport($charset, array $filters, Client $client, Users $user, $isBatch = false, $format = ReportFormat::CSV)
    {
        $this->charset = $charset;

        $content = [];

        $domain = $client->getDomain();

        if($client->isWhiteLabel()) {
            $domain = StringHelper::slugDomain($domain);
        }

        $this->setDefaultDatabaseOnMongo($domain);

        $columns   = [];
        $columns[] = 'Destinatário';
        $columns[] = 'Mensagem';
        $columns[] = 'Número enviado';
        $columns[] = 'Data';
        $columns[] = 'Ponto de acesso';

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

        if (array_key_exists('date_from', $filters) && $filters['date_from']) {
            $dateFrom = date('Y-m-d 00:00:00', strtotime(str_replace('/', '-', $filters['date_from'])));
            $dateFrom = new \DateTime($dateFrom);
        }

        if (array_key_exists('date_to', $filters) && $filters['date_to']) {
            $dateTo = date('Y-m-d 23:59:59', strtotime(str_replace('/', '-', $filters['date_to'])));
            $dateTo = new \DateTime($dateTo);
        }

        $filters = [
            'maxReportLinesPoc' => $filters['maxReportLinesPoc'],
            'filters' => [
                'date_from' => $dateFrom,
                'date_to'   => $dateTo
            ],
            'skip'  => 0
        ];

        $iterableResult = $this->em
            ->getRepository('DomainBundle:SmsHistoric')
            ->reportSms($client, null, null, $filters);

        $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);
        $guestFilter = [];
        if ($activeLegalBase->getLegalKind()->getKey() == LegalKinds::TERMO_CONSENTIMENTO) {
            $guestFilter['hasConsentRevoke']= [
                '$ne' => true
            ];
        }
        foreach ($iterableResult as $entity) {
            $guestFilter["mysql"] = $entity->getGuest()->getId();
            $guest = $this->mongo
                ->getRepository('DomainBundle:Guest\Guest')
                ->findOneBy($guestFilter);
            $accessPoint = 'Não informado';

            if (is_null($guest)) continue;
            if ($entity->getAccessPoint()) {
                $ap = $this->em
                    ->getRepository('DomainBundle:AccessPoints')
                    ->getAccessPointByIdentifier($entity->getAccessPoint(), $client);

                if ($ap) {
                    $accessPoint = $ap[0]->getFriendlyName();
                }
            }

            $row       = [];
            array_push($row, $guest->getProperties()[$guest->getLoginField()]);
            array_push($row, $this->utf8Fix($entity->getBodyMessage()));
            array_push($row, $entity->getSentTo());
            array_push($row, date_format($entity->getSentDate(), 'd/m/Y H:i:s'));
            array_push($row, $accessPoint);

            array_push($content, $row);

            if ($isBatch) {
                try {
                    $reportBatchDto->setContent($content);
                    $fileBuilder->addContent($reportBatchDto);
                    $reportBatchDto->clearContent();
                    unset($content);
                    $content = [];
                } catch (\Exception $e) {
                    $this->logger->addCritical(
                        "Erro ao criar o batch de Relatório de visitantes: " . $e->getMessage()
                    );
                }
            }

            $this->em->clear();
            $this->mongo->clear();

        }

        $report = new ReportDto();

        if ($isBatch) {
            $finalFile = $fileBuilder->build();
            $result = $this->fileUpload->uploadReports(
                $this->bucket,
                $client,
                ReportType::SMS,
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
