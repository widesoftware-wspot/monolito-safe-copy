<?php

namespace Wideti\DomainBundle\Service\Report\Reports;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Dto\ReportDto;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\DateTimeHelper;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\Auditor;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\DomainBundle\Service\Guest\Dto\GuestAccessReportFilterBuilder;
use Wideti\DomainBundle\Service\Guest\GuestService;
use Wideti\DomainBundle\Service\RadacctReport\Dto\GuestAccessReport;
use Wideti\DomainBundle\Service\RadacctReport\RadacctReportServiceAware;
use Wideti\DomainBundle\Service\Report\ReportFormat;
use Wideti\DomainBundle\Service\Report\ReportType;
use Wideti\DomainBundle\Service\ReportBuilder\ReportBuilderServiceAware;
use Wideti\DomainBundle\Service\ReportFileBuilder\ReportFileBuilder;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class GuestsReport implements Report
{
    use MongoAware;
    use EntityManagerAware;
    use SessionAware;
    use LoggerAware;
    use RadacctReportServiceAware;
    use ReportBuilderServiceAware;
    use CustomFieldsAware;

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
     * @var GuestService
     */
    private $guestService;
    private $bucket;

    /**
     * @var Auditor
     */
    private $auditor;

    /**
     * GuestsReport constructor.
     * @param $bucket
     * @param FileUpload $fileUpload
     * @param $tempFileFolder
     * @param GuestService $guestService
     * @param Auditor $auditor
     */
    public function __construct(
        $bucket,
        FileUpload $fileUpload,
        $tempFileFolder,
        GuestService $guestService,
        Auditor $auditor
    ) {
        $this->bucket = $bucket;
        $this->fileUpload = $fileUpload;
        $this->tempFileFolder = $tempFileFolder;
        $this->guestService = $guestService;
        $this->auditor = $auditor;
    }

    public function countResult(array $filters, Client $client)
    {
        $filter = $this->createFilterFrom($filters);
        $result = $this->guestService->retrieveGuestsIds(
            $client,
            $filter->getDateFrom(),
            $filter->getDateTo(),
            $filter
        );
        return count($result);
    }

    private function search($filters, $client)
    {
        $filter = $this->createFilterFrom($filters);
        return $this->guestService->getGuestInformationFromAccessDataReport(
            $client,
            $filter->getDateFrom(),
            $filter->getDateTo(),
            $filter
        );
    }

    /**
     * @param array $filters
     * @return GuestAccessReportFilter
     */
    private function createFilterFrom(array $filters)
    {
        // Feito esse tratamento pois fazemos um json_encode no filter na hora de enviar para o SNS, apenas por isso.
        if (count($filters) == 1 && gettype($filters[0]) === 'string') {
            $filters = json_decode($filters[0], true);
        }

        $dateFrom = \DateTime::createFromFormat('d/m/Y', $filters['dateFrom'])->setTime(00, 00, 00);
        $dateTo   = \DateTime::createFromFormat('d/m/Y', $filters['dateTo'])->setTime(23, 59, 59);

        return GuestAccessReportFilterBuilder::getBuilder()
            ->withFieldToFilter($filters['filter'])
            ->withDateFrom($dateFrom)
            ->withDateTo($dateTo)
            ->withRecurrence($filters['recurrence'])
            ->build();
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

        $content    = [];
        $columns    = [];

        $domain = $client->getDomain();

        if($client->isWhiteLabel()) {
            $domain = StringHelper::slugDomain($domain);
        }

        $this->setDefaultDatabaseOnMongo($domain);

        $loginField = $this->customFieldsService->getLoginField();

        $columns[] = $loginField[0]->getNameByLocale('pt_br');
        $columns[] = 'Nome';
        $columns[] = 'Data do cadastro';
        $columns[] = 'Data da última visita';
        $columns[] = 'Qtde de visitas';
        $columns[] = 'Download/Upload';
        $columns[] = 'Tempo médio de acesso';

        $fileBuilder    = null;
        $reportBatchDto = null;

        if ($isBatch) {
            $fileBuilder = new ReportFileBuilder($this->fileUpload, $this->tempFileFolder, $format);
            $reportBatchDto = new ReportDto();
            $reportBatchDto->setColumns($columns);
            $fileBuilder->addContent($reportBatchDto);
            $reportBatchDto->clearColumns();
        }

        $result = $this->search($filters, $client);

        /**
         * @var GuestAccessReport $guest
         */
        foreach ($result as $guest) {
            // Audit exportation
            $event = $this->auditor
                ->newEvent()
                ->withClient($client->getId())
                ->withSource(Kinds::userAdmin(), $user->getId())
                ->onTarget(Kinds::guest(), $guest->getUserNameId())
                ->withType(Events::export())
                ->addDescription(AuditEvent::PT_BR, 'Usuário exportou visitante a partir do relatório de visitantes')
                ->addDescription(AuditEvent::EN_US, 'User exported visitor from the visitor report')
                ->addDescription(AuditEvent::ES_ES, 'Visitante exportado por el usuario desde el informe de visitantes');
            $this->auditor->push($event);

            $download   = $this->convertByteToGBorMB($guest->getDownloadTotal());
            $upload     = $this->convertByteToGBorMB($guest->getUploadTotal());
            $row        = [];

            array_push($row, $guest->getLoginFieldValue() ?: 'N/I');
            array_push($row, $guest->getGuestName() ?: 'N/I');
            array_push($row, $guest->getRegisterDate());
            array_push($row, $guest->getLastAccessDate());
            array_push($row, $guest->getTotalOfVisits());
            array_push($row, "{$download} / {$upload}");
            array_push($row, DateTimeHelper::averageTimeFormat($guest->getAverageTime()));

            array_push($content, $row);

            if ($isBatch) {
                try {
                    if (count($content) == 1000 || end($content)) {
                        $reportBatchDto->setContent($content);
                        $fileBuilder->addContent($reportBatchDto);
                        $reportBatchDto->clearContent();
                        unset($content);
                        $content = [];
                    }
                } catch (\Exception $e) {
                    $this->logger->addCritical(
                        "Erro ao criar o batch de Relatório de visitantes: " . $e->getMessage()
                    );
                }
            }
        }

        $report = new ReportDto();

        if ($isBatch) {
            $finalFile = $fileBuilder->build();
            $result = $this->fileUpload->uploadReports(
                $this->bucket,
                $client,
                ReportType::GUESTS,
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

    private function convertByteToGBorMB($bytes)
    {
        $mb = ($bytes / 1024 / 1024);

        if ($mb >= 100000000) {
            $result = number_format(($mb/1024/1024/1024), 0, '.', '').' PB';
        } elseif ($mb >= 1000000 && $mb <= 100000000) {
            $result = number_format(($mb/1024/1024), 0, '.', '').' TB';
        } elseif ($mb >= 1024 && $mb <= 1000000) {
            $result = number_format(($mb/1024), 0, '.', '').' GB';
        } elseif (substr($mb, 0, 1) != 0) {
            $result = number_format($mb, 0, '.', '').' MB';
        } else {
            $result = number_format($mb, 2, '.', '').' MB';
        }

        return $result;
    }
}
