<?php

namespace Wideti\DomainBundle\Service\Report\Reports;

use Wideti\DomainBundle\Dto\ReportDto;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\Auditor;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Report\ReportFormat;
use Wideti\DomainBundle\Service\Report\ReportType;
use Wideti\DomainBundle\Service\ReportFileBuilder\ReportFileBuilder;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsService;

class BirthdaysReport implements Report
{
    use MongoAware;
    use EntityManagerAware;
    use SessionAware;
    use LoggerAware;

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
     * @var CustomFieldsService
     */
    private $customFieldService;

    /**
     * @var Auditor
     */
    private $auditor;

    /**
     * BirthdaysReport constructor.
     * @param $bucket
     * @param FileUpload $fileUpload
     * @param $tempFileFolder
     * @param ConfigurationService $configurationService
     * @param CustomFieldsService $customFieldService
     * @param Auditor $auditor
     */
    public function __construct(
        $bucket,
        FileUpload $fileUpload,
        $tempFileFolder,
        ConfigurationService $configurationService,
        CustomFieldsService $customFieldService,
        Auditor $auditor
    ) {
        $this->bucket = $bucket;
        $this->fileUpload = $fileUpload;
        $this->tempFileFolder = $tempFileFolder;
        $this->configurationService = $configurationService;
        $this->customFieldService = $customFieldService;
        $this->auditor = $auditor;
    }

    public function countResult(array $filters, Client $client)
    {
        $count = $this->search($filters);
        return count($count);
    }

    /**
     * @param $filters
     * @return array
     */
    private function search($filters)
    {
        $filters['filters'] = array_key_exists('month', $filters) ? $filters['month'] : '';

        $fields = $this->mongo
            ->getRepository('DomainBundle:CustomFields\Field')
            ->findSignUpFields();

        $guests = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->getGuestsByBirthDate($filters, $fields);

        $entities   = [];
        $row = [];

        foreach ($guests as $guest) {

            $guestProperties = $guest['properties'];

            foreach ($fields as $field) {

                $customPropertie = array_key_exists($field->getIdentifier(), $guestProperties)
                    ? $guestProperties[$field->getIdentifier()]
                    : 'Não informado';

                $row[$field->getIdentifier()] = $customPropertie;
            }

            $row["id"] = $guest["_id"];
            $row["mysql"] = $guest["mysql"];

            $row["opt-in"] = (array_key_exists('authorizeEmail', $guest) && $guest["authorizeEmail"] === "1") ? 'Sim' : 'Não';
            array_push($entities, $row);
        }

        return $entities;
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

        $content = [];

        $domain = $client->getDomain();

        if($client->isWhiteLabel()) {
            $domain = StringHelper::slugDomain($domain);
        }

        $this->setDefaultDatabaseOnMongo($domain);

        $hasOptInActive = true;

        $apGroups = $this->em
            ->getRepository('DomainBundle:AccessPointsGroups')
            ->findAll();

        if (count($apGroups) == 1) {
            $config = $this->configurationService->getDefaultConfiguration($client);
            $hasOptInActive = boolval($config['authorize_email']);
        }


        $fields = $this->customFieldService->getCustomFields();
        foreach ($fields as $field) {
            $randomColumnsPosition[] = $field->getPosition();
            $columns[] = $field->getNameByLocale('pt_br');
        }
        array_multisort($randomColumnsPosition, $columns);
        if ($hasOptInActive) {
            $columns[] = 'Aceita receber novidades';
        }

        $fileBuilder    = null;
        $reportBatchDto = null;

        if ($isBatch) {
            $fileBuilder = new ReportFileBuilder($this->fileUpload, $this->tempFileFolder, $format);
            $reportBatchDto = new ReportDto();
            $reportBatchDto->setColumns($columns);
            $fileBuilder->addContent($reportBatchDto);
            $reportBatchDto->clearColumns();
        }

        $iterableResult = $this->search($filters);

        foreach ($iterableResult as $entity) {
            // Audit exportation
            $event = $this->auditor
                ->newEvent()
                ->withClient($client->getId())
                ->withSource(Kinds::userAdmin(), $user->getId())
                ->onTarget(Kinds::guest(), $entity['mysql'])
                ->withType(Events::export())
                ->addDescription(AuditEvent::PT_BR, 'Usuário exportou visitante a partir do relatório de aniversariantes do mês')
                ->addDescription(AuditEvent::EN_US, 'User exported visitor from month birthday report')
                ->addDescription(AuditEvent::ES_ES, 'Visitante exportado por el usuario del informe de cumpleaños del mes');
            $this->auditor->push($event);

            $row = [];
            foreach ($entity as $key=> $e) {
                if (is_object($e)) {
                    $className = get_class($e);
                    if ($className !== "MongoId") {
                        array_push($row, $e);
                    }
                } else {
                    if ($key == "mysql") {
                        continue;
                    }
                    array_push($row, $e);
                }
            }

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

            $this->mongo->clear();
        }

        $report = new ReportDto();

        if ($isBatch) {
            $finalFile = $fileBuilder->build();
            $result = $this->fileUpload->uploadReports(
                $this->bucket,
                $client,
                ReportType::BIRTHDAYS,
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
