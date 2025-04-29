<?php

namespace Wideti\DomainBundle\Service\Report\Reports;

use Wideti\DomainBundle\Dto\ReportDto;
use Wideti\DomainBundle\Entity\CallToActionAccessData;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\LegalKinds;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Repository\CampaignCallToAction\AccessDataRepository;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\Auditor;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\DomainBundle\Service\LegalBaseManager\LegalBaseManagerService;
use Wideti\DomainBundle\Service\Report\ReportFormat;
use Wideti\DomainBundle\Service\Report\ReportType;
use Wideti\DomainBundle\Service\ReportFileBuilder\ReportFileBuilder;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class CallToActionReport implements Report
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
	 * @var AccessDataRepository
	 */
	private $callToActionRepository;

    /**
     * @var Auditor
     */
	private $auditor;

    private $legalBaseManager;
    /**
     * CallToActionReport constructor.
     * @param $bucket
     * @param FileUpload $fileUpload
     * @param $tempFileFolder
     * @param AccessDataRepository $callToActionRepository
     * @param Auditor $auditor
     */
	public function __construct(
        $bucket,
        FileUpload $fileUpload,
        $tempFileFolder,
        AccessDataRepository $callToActionRepository,
        Auditor $auditor,
        LegalBaseManagerService $legalBaseManagerService
    ) {
        $this->bucket = $bucket;
        $this->fileUpload = $fileUpload;
        $this->tempFileFolder = $tempFileFolder;
        $this->callToActionRepository = $callToActionRepository;
        $this->auditor = $auditor;
        $this->legalBaseManager = $legalBaseManagerService;
    }

	public function countResult(array $filters, Client $client)
	{
		$campaignId = isset($filters['campaignId']) ? $filters['campaignId'] : null;

		if (!$campaignId) return null;

		return $this->callToActionRepository
			->countByCampaignId($campaignId);
	}

    /**
     * @param $charset
     * @param array $filters
     * @param Client $client
     * @param Users $user
     * @param bool $isBatch
     * @param string $format
     * @return ReportDto
     * @throws \Wideti\DomainBundle\Service\AuditLogs\AuditException
     */
	public function getReport($charset, array $filters, Client $client, Users $user, $isBatch = false, $format = ReportFormat::CSV)
	{
		if ($isBatch) {
			$filters = json_decode($filters[0], true);
		}
		$this->charset = $charset;

		$content = [];

        $domain = $client->getDomain();

        if($client->isWhiteLabel()) {
            $domain = StringHelper::slugDomain($domain);
        }

		$this->setDefaultDatabaseOnMongo($domain);

		$columns   = [];
		$columns[] = 'Campanha';
		$columns[] = 'Tipo Call To Action';
		$columns[] = 'Visitante';
		$columns[] = 'Mac Address Visitante';
		$columns[] = 'Ponto de Acesso';
		$columns[] = 'URL';
		$columns[] = 'Data/Hora';

		$fileBuilder    = null;
		$reportBatchDto = null;

		if ($isBatch) {
			$fileBuilder = new ReportFileBuilder($this->fileUpload, $this->tempFileFolder, $format);
			$reportBatchDto = new ReportDto();
			$reportBatchDto->setColumns($columns);
			$fileBuilder->addContent($reportBatchDto);
			$reportBatchDto->clearColumns();
		}

		$filters = [
			'maxReportLinesPoc' => $filters['maxReportLinesPoc'],
			'filters' => [
				'campaignId' => $filters['campaignId'],
				'type' => $filters['type'],
				'dateFrom' => $filters['date_from'],
				'dateTo' => $filters['date_to']
			],
			'skip' => 0
		];

		$iterableResult = $this->em
			->getRepository('DomainBundle:CallToActionAccessData')
			->reportCta(null, null, $filters);

        $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);
        $guestFilter = [];
        if ($activeLegalBase->getLegalKind()->getKey() == LegalKinds::TERMO_CONSENTIMENTO) {
            $guestFilter['hasConsentRevoke']= [
                '$ne' => true
            ];
        }
		/**
		 * @var CallToActionAccessData $entity
		 */
		foreach ($iterableResult as $entity) {
            $guestFilter["mysql"] = $entity->getGuestId();
			$guest = 'Não informado';

			if ($entity->getGuestId()) {
				$guest = $this->mongo
					->getRepository('DomainBundle:Guest\Guest')
					->findOneBy($guestFilter);

                if (is_null($guest)) continue;
                // Audit exportation
                $event = $this->auditor
                    ->newEvent()
                    ->withClient($client->getId())
                    ->withSource(Kinds::userAdmin(), $user->getId())
                    ->onTarget(Kinds::guest(), $guest->getMysql())
                    ->withType(Events::export())
                    ->addDescription(AuditEvent::PT_BR, 'Usuário exportou call to action')
                    ->addDescription(AuditEvent::EN_US, 'User exported call to action')
                    ->addDescription(AuditEvent::ES_ES, 'Usuario exportado call to action');
                $this->auditor->push($event);

                $guest = ($guest)?
                    $guest->getProperties()[$guest->getLoginField()]
                    :"Não informado";

			}


			$accessPoint = 'Não informado';

			if ($entity->getApMacAddress()) {
				$ap = $this->em
					->getRepository('DomainBundle:AccessPoints')
					->getAccessPointByIdentifier($entity->getApMacAddress(), $client);

				if ($ap) {
					$accessPoint = $ap[0]->getFriendlyName();
				}
			}

			$row       = [];
			array_push($row, $entity->getCampaign()->getName());
			array_push($row, ($entity->getType() == 1) ? 'Pré-login' : 'Pós-login');
			array_push($row, $guest);
			array_push($row, $entity->getMacAddress() ?: 'Não informado');
			array_push($row, $accessPoint ?: 'Não informado');
			array_push($row, $entity->getUrl() ?: 'Não informado');
			array_push($row, date_format($entity->getViewDate(), 'd/m/Y H:i:s'));

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
                ReportType::CALL_TO_ACTION,
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
