<?php

namespace Wideti\AdminBundle\Controller;


use Aws\Sns\Message;
use Exception;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Wideti\AdminBundle\Form\Type\Reports\AuditLogFilterType;
use Wideti\AdminBundle\Form\Type\Reports\ReportFilterType;
use Wideti\AdminBundle\Form\Type\Reports\CampaignReportFilterType;
use Wideti\AdminBundle\Form\Type\Reports\CampaignViewsFilterType;
use Wideti\AdminBundle\Form\Type\Reports\DateFromToType;
use Wideti\AdminBundle\Form\Type\Reports\DateFromToWithLimitType;
use Wideti\AdminBundle\Form\Type\Reports\downloadUploadFilterType;
use Wideti\AdminBundle\Form\Type\Reports\GuestReportFilterType;
use Wideti\AdminBundle\Form\Type\Reports\MonthFilterType;
use Wideti\AdminBundle\Form\Type\Reports\OnlineUsersFilterType;
use Wideti\AdminBundle\Form\Type\Reports\SmsReportFilterType;
use Wideti\DomainBundle\Entity\CampaignViews;
use Wideti\DomainBundle\Entity\LegalKinds;
use Wideti\DomainBundle\Entity\Plan;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Exception\ClientNotFoundException;
use Wideti\DomainBundle\Exception\ClientPlanNotFoundException;
use Wideti\DomainBundle\Exception\NotAuthorizedPlanException;
use Wideti\DomainBundle\Gateways\Consents\GetConsentGateway;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Helpers\DateTimeHelper;
use Wideti\DomainBundle\Helpers\Pagination;
use Wideti\DomainBundle\Helpers\WifiMode;
use Wideti\DomainBundle\Repository\CampaignCallToAction\AccessDataRepository;
use Wideti\DomainBundle\Repository\Elasticsearch\Report\ReportRepositoryAware;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\AuditException;
use Wideti\DomainBundle\Service\AuditLogs\Auditor;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\DomainBundle\Service\Guest\DisconnectGuestProducer;
use Wideti\DomainBundle\Service\Guest\Dto\GuestAccessReportFilterBuilder;
use Wideti\DomainBundle\Service\Guest\GuestService;
use Wideti\DomainBundle\Service\Jaeger\TracerHeaders;
use Wideti\DomainBundle\Service\LegalBaseManager\LegalBaseManagerService;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\DomainBundle\Service\Plan\PlanAssert;
use Wideti\DomainBundle\Service\RadacctReport\RadacctReportServiceAware;
use Wideti\DomainBundle\Service\Report\ReportServiceAware;
use Wideti\DomainBundle\Service\Report\ReportType;
use Wideti\DomainBundle\Service\ReportBuilder\ReportBuilderServiceAware;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\PaginatorAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class ReportController
{
	use EntityManagerAware;
	use MongoAware;
	use TwigAware;
	use ReportBuilderServiceAware;
	use SessionAware;
	use PaginatorAware;
	use RadacctReportServiceAware;
	use ReportServiceAware;
	use LoggerAware;
	use CustomFieldsAware;
	use ReportRepositoryAware;
	use ModuleAware;

	private $maxDownload;
	private $maxReportLinesPoc;
	/**
	 * @var AdminControllerHelper
	 */
	private $controllerHelper;
	/**
	 * @var ConfigurationService
	 */
	private $configurationService;
	/**
	 * @var GuestService
	 */
	private $guestService;
	/**
	 * @var WifiMode
	 */
	private $wifiMode;
	/**
	 * @var AccessDataRepository
	 */
	private $callToActionRepository;

	/**
	 * @var Auditor
	 */
	private $auditor;

	/**
	 * @var GetConsentGateway
	 */
	private $getConsentGateway;

    /**
     * @var LegalBaseManagerService
     */
    private $legalBaseManager;

    /**
     * @var AuthorizationChecker
     */
    protected $authorizationChecker;

	/**
	 * @var DisconnectGuestProducer
	 */
	private	$disconnectGuestProducer;
	/**
	 * ReportController constructor.
	 * @param ConfigurationService $configurationService
	 * @param $maxDownload
	 * @param $maxReportLinesPoc
	 * @param AdminControllerHelper $controllerHelper
	 * @param GuestService $guestService
	 * @param WifiMode $wifiMode
	 * @param AccessDataRepository $callToActionRepository
	 * @param Auditor $auditor
	 * @param GetConsentGateway $getConsentGateway
	 * @param DisconnectGuestProducer $disconnectGuestProducer
	 */
	public function __construct(
		ConfigurationService $configurationService,
		$maxDownload,
		$maxReportLinesPoc,
		AdminControllerHelper $controllerHelper,
		GuestService $guestService,
		WifiMode $wifiMode,
		AccessDataRepository $callToActionRepository,
		Auditor $auditor,
		GetConsentGateway $getConsentGateway,
    LegalBaseManagerService $legalBaseManagerService,
		DisconnectGuestProducer $disconnectGuestProducer
	)
	{
		$this->maxDownload = $maxDownload;
		$this->maxReportLinesPoc = $maxReportLinesPoc;
		$this->controllerHelper = $controllerHelper;
		$this->configurationService = $configurationService;
		$this->guestService = $guestService;
		$this->wifiMode = $wifiMode;
		$this->callToActionRepository = $callToActionRepository;
		$this->auditor = $auditor;
		$this->getConsentGateway = $getConsentGateway;
    $this->legalBaseManager = $legalBaseManagerService;
		$this->disconnectGuestProducer = $disconnectGuestProducer;
	}

	/**
	 * @param Request $request
	 * @param int $page
	 * @return Response|null
	 * @throws ClientPlanNotFoundException
	 * @throws NotAuthorizedPlanException
	 * @throws AuditException
	 */
	public function historicAction(Request $request, $page = 1)
	{
        if (
            $this->authorizationChecker->isGranted('ROLE_USER_LIMITED') ||
            $this->authorizationChecker->isGranted('ROLE_MARKETING_LIMITED') ||
            $this->authorizationChecker->isGranted('ROLE_USER_BASIC')
        ) {
            throw new AccessDeniedException(('Unauthorized access!'));
        }
		$client = $this->getLoggedClient();
		$user = $this->controllerHelper->getUser();
		$traceHeaders = TracerHeaders::from($request);
		$consent = $this->getConsentGateway->get($client, 'pt_BR', $traceHeaders);

		PlanAssert::checkOrThrow($client, Plan::PRO);

		$client = $this->getLoggedClient();
		$dateFrom = date_format(new \DateTime("NOW -30 days"), 'Y-m-d 00:00:00');
		$dateTo = date_format(new \DateTime("NOW"), 'Y-m-d H:i:s');

		$range = [
			"range" => [
				"acctstarttime" => [
					"gte" => $dateFrom,
					"lte" => $dateTo
				]
			]
		];

		$filterForm = $this->controllerHelper->createForm(
			ReportFilterType::class,
			null,
			['attr' => ["client" => $client->getId()]]
		);

		$filterForm->handleRequest($request);

		if ($filterForm->isValid()) {
			$filter = $filterForm->get('filter')->getData();
			$value = $filterForm->get('value')->getData() ? $filterForm->get('value')->getData() : "";

			if ($filter == 'calledstation_name' && $filterForm->get('access_point')->getData() != null) {
				$ap = $this->em
					->getRepository('DomainBundle:AccessPoints')
					->getManyAccessPointById($filterForm->get('access_point')->getData()->getId());

				$filters[] = [
					"term" => [
						$filter => $ap[0]["friendlyName"]
					]
				];
			}

			if ($filter == 'framedipaddress' || $filter == 'callingstationid') {
				$filters[] = [
					"term" => [
						$filter => strtoupper($value)
					]
				];
			}

            $filtros = ['filters' =>
                [
                'filtro' => $filter,
                'value' => $value
                ]
            ];
            $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);
            if ($activeLegalBase->getLegalKind()->getKey() == LegalKinds::TERMO_CONSENTIMENTO) {
                $filtros['filters']['hasConsentRevoke']  = true;
            }

			if ($value != null) {
				$result = $this->mongo
					->getRepository('DomainBundle:Guest\Guest')
					->searchQuery($filtros);

				$guest = $result->getSingleResult();

				if ($guest) {
					$filters[] = [
						'term' => [
							'username' => $guest->getMysql()
						]
					];
				}
			}

			$dateFrom = $filterForm->get('date_from')->getData()->format("Y-m-d 00:00:00");
			$dateTo = $filterForm->get('date_to')->getData()->format("Y-m-d 23:59:59");

			$range = [
				"range" => [
					"acctstarttime" => [
						"gte" => $dateFrom,
						"lte" => $dateTo
					]
				]
			];
		}

		$filters[] = [
			"query" => [
				"filtered" => [
					"filter" => [
						"and" => [
							"filters" => [
								[
									"term" => ["client_id" => $client->getId()]
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

		$filters[] = $range;
		$period = [
			'from' => $dateFrom,
			'to' => $dateTo
		];

		$pocStatus = \Wideti\DomainBundle\Entity\Client::STATUS_POC;

		$filters = [
			'maxReportLinesPoc' => ($client->getStatus() == $pocStatus) ? $this->maxReportLinesPoc : null,
			'filters' => $filters
		];

		$accountings = $this->radacctReportService->findAccountingByFilter($filters, $period, $page);

		$accountingsTotal = $this->radacctReportService->countByQuery($filters, $period);
		$loginField = $this->customFieldsService->getLoginField()[0];

		$pagination = new Pagination($page, $accountingsTotal, 10);
		$pagination_array = $pagination->createPagination();

        $guestsConsentRevokeId  = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->getConsentRevokesId();
        $guestsConsentRevokeId = $guestsConsentRevokeId->toArray();

        $idsRevoked = [];
        foreach($guestsConsentRevokeId as $gg) {
            $idsRevoked[] = $gg->mysql;
        }

		// Auditoria
		$guestIds = [];
		foreach ($accountings as $k => $a) {
            if (in_array($a['username'], $idsRevoked)) {
                unset($accountings[$k]);
            } else {
                $guestIds[] = $a['username'];
            }
		}

		$uniqueGuestIds = array_unique($guestIds);

		foreach ($uniqueGuestIds as $id) {
			$event = $this->auditor
				->newEvent()
				->withClient($client->getId())
				->withSource(Kinds::userAdmin(), $user->getId())
				->onTarget(Kinds::guest(), $id)
				->withType(Events::view())
				->addDescription(AuditEvent::PT_BR, 'Usuário visualizou vistante na listagem de histórico de acesso')
				->addDescription(AuditEvent::EN_US, 'User viewed visitor in the access history listing')
				->addDescription(AuditEvent::ES_ES, 'Visitante visto por el usuario en la lista del historial de acceso');
			$this->auditor->push($event);
		}

		return $this->render(
			'AdminBundle:Report:historico.html.twig',
			[
				'client' => $client,
				'maxReportLines' => $this->maxReportLinesPoc,
				'loginField' => $loginField,
				'count' => ($client->getStatus() == $pocStatus) ? count($accountings) : $accountingsTotal,
				'maxDownload' => $this->maxDownload,
				'accountings' => $accountings,
				'date_from' => $dateFrom,
				'date_to' => $dateTo,
				'pagination' => $pagination_array,
				'filter' => $filterForm->createView(),
				'reportType' => ReportType::ACCESS_HISTORIC,
				'consent' => $consent
			]
		);
	}

	public function smsReportAction(Request $request, $page = 1)
	{
        if (
            $this->authorizationChecker->isGranted('ROLE_USER_LIMITED') ||
            $this->authorizationChecker->isGranted('ROLE_MARKETING_LIMITED') ||
            $this->authorizationChecker->isGranted('ROLE_USER_BASIC')
        ) {
            throw new AccessDeniedException(('Unauthorized access!'));
        }
		$client = $this->getLoggedClient();
		PlanAssert::checkOrThrow($client, Plan::PRO);

		$client = $this->getLoggedClient();
		$filters = [];

		$filters['date_from'] = date_format(new \DateTime("NOW -30 days"), 'Y-m-d 00:00:00');
		$filters['date_to'] = date_format(new \DateTime("NOW"), 'Y-m-d 23:59:59');

		$form = $this->controllerHelper->createForm(SmsReportFilterType::class);

		$form->handleRequest($request);

		if ($form->isValid()) {
			$filters = $form->getData();
			$filters['date_to'] = date_format($filters['date_to'], 'Y-m-d 23:59:59');
		}

		$pocStatus = \Wideti\DomainBundle\Entity\Client::STATUS_POC;


		$filters = [
			'maxReportLinesPoc' => ($client->getStatus() == $pocStatus) ? $this->maxReportLinesPoc : null,
			'filters' => $filters
		];

        $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);
        if ($activeLegalBase->getLegalKind()->getKey() == LegalKinds::TERMO_CONSENTIMENTO) {
            $filters['filters']['hasConsentRevoke']  = true;
        }

		$count = $this->em
			->getRepository('DomainBundle:SmsHistoric')
			->reportSms($client, null, null, $filters, true);;

		$pagination = new Pagination($page, $count, 20);
		$paginationData = $pagination->createPagination();

		$historic = $this->em
			->getRepository('DomainBundle:SmsHistoric')
			->reportSms(
				$client,
				$pagination->getPerPage(),
				$paginationData['offset'],
				$filters
			);


        $guestsConsentRevokeId  = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->getConsentRevokesId();
        $guestsConsentRevokeId = $guestsConsentRevokeId->toArray();

        $idsRevoked = [];
        foreach($guestsConsentRevokeId as $gg) {
            $idsRevoked[] = $gg->mysql;
        }
        foreach ($historic as $k => $h) {
            if (in_array($h->getGuest()->getId(), $idsRevoked)) {
                unset($historic[$k]);
            }
        }

        return $this->render(
			'AdminBundle:Report:smsHistoric.html.twig',
			[
				'client' => $client,
				'maxReportLines' => $this->maxReportLinesPoc,
				'count' => $count,
				'historic' => $historic,
				'pagination' => $paginationData,
				'filter' => $form->createView(),
				'reportType' => ReportType::SMS
			]
		);
	}

	public function downloadUploadAction(Request $request, $page = 1)
	{
		$periodFrom = date('Y');
		$periodTo = date('Y') . '||+1y';
		$formatRange = "yyyy";
		$formatAggs = "yyyy-MM";
		$interval = "month";
		$filtered = false;
		$apsString = '';

		$client = $this->getLoggedClient();
		PlanAssert::checkOrThrow($client, Plan::PRO);
		$options['attr']['client'] = $client->getId();

		$filterForm = $this
			->controllerHelper
			->createForm(downloadUploadFilterType::class, null, $options);

		$filterForm->handleRequest($request);

		$access_point = [];

		if ($filterForm->isValid()) {
			$dataFilter = $filterForm->getData();
			$access_point = $dataFilter['access_point'];
			$periodFrom = $dataFilter['year'] . '-' . $dataFilter['month'];
			$periodTo = $dataFilter['year'] . '-' . $dataFilter['month'] . '||+1M-1d';
			$formatRange = "yyyy-MM";
			$filtered = true;

			if ($access_point) {
				foreach ($access_point as $data) {
					$apsString .= $data . ',';
				}

				$accessPoints = $this->em
					->getRepository('DomainBundle:AccessPoints')
					->getManyAccessPointById($access_point);

				$access_point = [];

				foreach ($accessPoints as $aps) {
					array_push($access_point, ["match" => ["identifier" => $aps['macAddress']]]);
				}
			}
		}

		$period = [
			'from' => $periodFrom,
			'to' => $periodTo
		];

		$downloadUpload = $this->reportRepository->getDownloadUploadByDate(
			$client,
			$period,
			$access_point,
			'download',
			'upload',
			$interval,
			$formatRange,
			$formatAggs
		);

		$count = count($downloadUpload['aggregations']['download_upload']['buckets']);
		$pagination = new Pagination($page, $count, 20);
		$pagination_array = $pagination->createPagination();

		return $this->render(
			'AdminBundle:Report:downloadUpload.html.twig',
			[
				'hasResult' => (bool)$count,
				'entity' => $downloadUpload['aggregations']['download_upload']['buckets'],
				'accessPoint' => $access_point,
				'apsToGraph' => substr($apsString, 0, -1),
				'pagination' => $pagination_array,
				'filter' => $filterForm->createView(),
				'filtered' => $filtered,
				'reportType' => ReportType::DOWNLOAD_UPLOAD
			]
		);
	}

	public function downloadUploadDetailAction(Request $request)
	{
		$client = $this->getLoggedClient();
		PlanAssert::checkOrThrow($client, Plan::PRO);

		$client = $this->getLoggedClient();
		$periodFrom = $request->get('year') . '-' . $request->get('month');
		$periodTo = $request->get('year') . '-' . $request->get('month') . '||+1M-1d';
		$formatRange = "yyyy-MM";
		$formatAggs = "yyyy-MM-dd";
		$interval = "day";
		$access_point = $request->get('accessPoint');

		$period = [
			'from' => $periodFrom,
			'to' => $periodTo
		];

		$details = $this->reportRepository->getDownloadUploadByDate(
			$client,
			$period,
			$access_point,
			'download',
			'upload',
			$interval,
			$formatRange,
			$formatAggs
		);

		$results = $details['aggregations']['download_upload']['buckets'];
		$entity = [];

		foreach ($results as $result) {
			if ($result['doc_count'] == 0) {
				continue;
			}
			array_push($entity, $result);
		}

		return $this->render(
			'AdminBundle:Report:downloadUploadDetail.html.twig',
			[
				'entity' => $entity,
				'reportType' => ReportType::DOWNLOAD_UPLOAD_DETAIL
			]
		);
	}

	public function downloadUploadChartsAction(Request $request)
	{
		$client = $this->getLoggedClient();
		$accessPoint = $request->get('accessPoint');
		$month = $request->get('month');
		$year = $request->get('year');
		$type = 'month';

		$dateFrom = $year . "-01";
		$dateTo = $year . "-12||+1M-1d";
		$interval = 'month';
		$formatAggregation = "MM";
		$formatRange = "yyyy-MM";
		$access_point = null;

		if ($accessPoint) {
			$accessPoints = $this->em
				->getRepository('DomainBundle:AccessPoints')
				->getManyAccessPointById(explode(',', $accessPoint));

			$access_point = [];

			foreach ($accessPoints as $aps) {
				array_push($access_point, ["term" => ["friendlyName" => $aps['friendlyName']]]);
			}
		}

		if ($month && $year) {
			$dateFrom = $year . "-" . $month;
			$dateTo = $year . "-" . $month . "||+1M-1d";
			$interval = "day";
			$formatAggregation = "yyyy-MM-dd";
			$type = null;
		}

		$period = [
			'from' => $dateFrom,
			'to' => $dateTo
		];

		$downloadUpload = $this->reportRepository->getDownloadUploadByDate(
			$client,
			$period,
			$access_point,
			'download',
			'upload',
			$interval,
			$formatRange,
			$formatAggregation
		);

		$details = $this->reportBuilder->downloadUploadData($downloadUpload, $type);

		return new JsonResponse($details);
	}

	public function accessPointAction(Request $request)
	{
		$client = $this->getLoggedClient();
		PlanAssert::checkOrThrow($client, Plan::PRO);
		$options['attr']['dashboard'] = $request->get('dashboard');
		$options['attr']['client'] = $client->getId();

		$filter = $this
			->controllerHelper
			->createForm(DateFromToType::class, null, $options);

		$filter->handleRequest($request);

		$date_from = new \DateTime("NOW -30 days");
		$date_to = new \DateTime("NOW");
		$access_point = [];

		if ($filter->isValid()) {
			$dateFilter = $filter->getData();
			$date_from = $dateFilter['date_from'];
			$access_point = $dateFilter['access_point'];

			if (null == !$dateFilter['date_to']) {
				$date_to = $dateFilter['date_to']->setTime(23, 59, 59);
			} else {
				$date_to = null;
			}

			if ($access_point) {
				$accessPoints = $this->em
					->getRepository('DomainBundle:AccessPoints')
					->getManyAccessPointById($access_point);

				$access_point = [];

				foreach ($accessPoints as $aps) {
					array_push(
						$access_point,
						["term" => ["friendlyName" => $aps['friendlyName']]]
					);
				}
			}
		}

		$period = [
			'date_from' => date_format($date_from, 'Y-m-d'),
			'date_to' => date_format($date_to, 'Y-m-d')
		];

		$result = $this->radacctReportService->getVisitsAndRecordsPerAccessPoint(
			$client,
			$period,
			$access_point,
			10
		);

		$chartData = [
			'signIns' => [],
			'signUps' => []
		];

		foreach ($result as $data) {
			array_push(
				$chartData['signIns'],
				[
					'label' => $data['key'],
					'data' => $data['totalVisits']['value']
				]
			);

			array_push(
				$chartData['signUps'],
				[
					'label' => $data['key'],
					'data' => $data['totalRegistrations']['value']
				]
			);
		}

		return $this->render(
			'AdminBundle:Report:accessPoint.html.twig',
			[
				'result' => $result,
				'filter' => $filter->createView(),
				'jsonChart' => $chartData,
				'reportType' => ReportType::ACCESS_POINTS
			]
		);
	}

	public function campaignAction(Request $request)
	{
		$client = $this->getLoggedClient();
		PlanAssert::checkOrThrow($client, Plan::PRO);

		$client = $this->getLoggedClient();

		$options['attr']['accessPointFilter'] = true;
		$options['attr']['client'] = $client->getId();

		$filter = $this->controllerHelper->createForm(CampaignReportFilterType::class, null, $options);
		$filter->handleRequest($request);

		$filterParams = null;
		$campaign = null;
		$date_from = null;
		$date_to = null;

		if ($filter->isValid()) {
			$dateFilter = $filter->getData();
			$campaign = $dateFilter['campaign'];
			$date_from = $dateFilter['date_from'];
			$date_to = $dateFilter['date_to'];
		}

		$campaignViews = $this->em
			->getRepository("DomainBundle:CampaignViews")
			->getMostViewedHours($client->getId(), $campaign, $date_from, $date_to);

		$chartData = [
			'campaignViews' => []
		];

		foreach ($campaignViews as $data) {
			array_push(
				$chartData['campaignViews'],
				[
					'label' => $data['name'],
					'data' => $data['total']
				]
			);
		}

		return $this->render(
			'AdminBundle:Report:campaign.html.twig',
			[
				'campaignViews' => $campaignViews,
				'filter' => $filter->createView(),
				'jsonChart' => $chartData,
				'reportType' => ReportType::CAMPAIGN
			]
		);
	}

	public function campaignDetailAction(Request $request)
	{
		$client = $this->getLoggedClient();
		PlanAssert::checkOrThrow($client, Plan::PRO);

		$params = [];

		if ($request->isMethod('GET')) {
			$filterParams = $request->query->all();

			if ($filterParams) {
				foreach ($filterParams['campaignReportFilter'] as $key => $value) {
					$params[$key] = $value;
				}
			}
		}

		$campaign = $this->em
			->getRepository("DomainBundle:Campaign")
			->findOneById($request->get('id'));

		$views = $this->em
			->getRepository("DomainBundle:CampaignViews")
			->getMostViewedHoursByCampaign(
				$request->get('id'),
				$params
			);

		return $this->render(
			'AdminBundle:Report:campaignDetail.html.twig',
			[
				'campaign' => $campaign,
				'views' => $views
			]
		);
	}

	public function viewDetails(Request $request)
	{
		$client = $this->getLoggedClient();
		PlanAssert::checkOrThrow($client, Plan::PRO);

		$views = [];
		$numberOfViews = 0;

		$form = $this->controllerHelper->createForm(CampaignViewsFilterType::class);
		$form->handleRequest($request);

		if ($form->isValid()) {
			$filters = $form->getData();
			$filters['date_from'] = date_format($filters['date_from'], 'Y-m-d');
			$filters['date_to'] = date_format($filters['date_to'], 'Y-m-d');
		} else {
			$filters['date_to'] = date('Y-m-d');
			$filters['date_from'] = date('Y-m-d', strtotime($filters['date_to'] . '-30 days'));
		}

		$campaignViews = $this->em
			->getRepository('DomainBundle:CampaignViews')
			->getCampaignViews($request->get('id'), $request->get('type'), $filters);

		foreach ($campaignViews as $key => $value) {
			$numberOfViews += $value['quantity'];

			$views[] = [
				'guestMacAddress' => $value['guest'],
				'accessPoint' => $value['access_point'],
				'time' => $value['view_time'],
				'quantity' => $value['quantity'],
				'detailsLink' => true
			];
		}

		$pagination = $this->paginator->paginate($views, $request->get('page'), 20);

		return $this->render(
			'AdminBundle:Report:campaignViewsDetail.html.twig',
			[
				'numberOfViews' => $numberOfViews,
				'noData' => (count($views) == 0),
				'filter' => $form->createView(),
				'campaign' => $request->get('id'),
				'type' => $request->get('type'),
				'campaignViews' => $pagination->getItems(),
				'pagination' => $pagination,
				'viewTitle' => $request->get('type') == CampaignViews::STEP_PRE ? "Pré" : "Pós"
			]
		);
	}

	public function campaignCTAAction(Request $request)
	{
		$client = $this->getLoggedClient();
		PlanAssert::checkOrThrow($client, Plan::PRO);

		$client = $this->getLoggedClient();

		$options['attr']['client'] = $client->getId();

		$filter = $this->controllerHelper->createForm(CampaignReportFilterType::class, null, $options);
		$filter->handleRequest($request);

		$filterParams = null;
		$campaign = null;
		$access_point = null;

		if ($filter->isValid()) {
			$dateFilter = $filter->getData();
			$campaign = $dateFilter['campaign'];
			$date_from = $dateFilter['date_from'];

			if (null == !$dateFilter['date_to']) {
				$date_to = $dateFilter['date_to']->setTime(23, 59, 59);
			} else {
				$date_to = null;
			}

		} else {
			$date_from = null;
			$date_to = null;
		}

		$campaignViews = $this
			->callToActionRepository
			->getCampaignsWithMoreClicks($client->getId(), $campaign, $access_point, $date_from, $date_to);

		$chartMoreClicks = $this->campaignCTAChartClicksByCampaign($campaignViews);
		$chartByDays = $this->campaignCTAChartClicksByDays($client->getId(), $campaign, $access_point, $date_from, $date_to);
		$chartByHours = $this->campaignCTAChartClicksByHours($client->getId(), $campaign, $access_point, $date_from, $date_to);

		return $this->render(
			'AdminBundle:Report:campaignCallToAction.html.twig',
			[
				'campaignViews' => $campaignViews,
				'filter' => $filter->createView(),
				'moreClicksChart' => $chartMoreClicks,
				'reportType' => ReportType::CAMPAIGN,
				'clicksByDayOfWeekChart' => $chartByDays,
				'clicksByHourChart' => $chartByHours
			]
		);
	}

	public function campaignCTAChartClicksByCampaign($campaignViews)
	{
		$chartData = [
			'campaignViews' => []
		];

		foreach ($campaignViews as $data) {
			array_push(
				$chartData['campaignViews'],
				[
					'label' => $data['campanha'],
					'data' => $data['quantidade']
				]
			);
		}

		return $chartData;
	}

	public function campaignCTAChartClicksByDays($clientId, $campaign, $accessPoint, $dateFrom, $dateTo)
	{
		$dateFrom = $dateFrom ?: new \DateTime("NOW -30 days");
		$dateTo = $dateTo ?: new \DateTime("NOW");

		$dateFrom = $dateFrom->format('Y-m-d 00:00:00');
		$dateTo = $dateTo->format('Y-m-d 23:59:59');

		$records = $this
			->callToActionRepository
			->getCampaignsWithMoreClicksByDayOfWeek($clientId, $campaign, $accessPoint, $dateFrom, $dateTo);

		$period = new \DatePeriod(
			new \DateTime($dateFrom),
			new \DateInterval("P1D"),
			new \DateTime($dateTo)
		);

		$totalGraph = [];

		foreach ($period as $date) {
			$totalGraph[$date->format("d/m")] = 0;
		}

		$perDayGraph = DateTimeHelper::daysOfWeek();
		$perDayList = [];

		foreach ($records as $data) {
			$totalRegisters = $data['quantity'];
			$dayOfWeek = date('w', strtotime($data['view_date']));
			$perDayGraph[$dayOfWeek] += (int)$totalRegisters;
		}

		foreach ($perDayGraph as $key => $value) {
			$perDayList[$key] = is_int($value)
				? $value
				: 0;
		}

		return [
			'categories' => array_values(DateTimeHelper::daysOfWeek()),
			'values' => array_values($perDayGraph)
		];
	}

	public function campaignCTAChartClicksByHours($clientId, $campaign, $accessPoint, $dateFrom, $dateTo)
	{
		$dateFrom = $dateFrom ?: new \DateTime("NOW -30 days");
		$dateTo = $dateTo ?: new \DateTime("NOW");

		$dateFrom = $dateFrom->format('Y-m-d 00:00:00');
		$dateTo = $dateTo->format('Y-m-d 23:59:59');

		$records = $this
			->callToActionRepository
			->getCampaignsWithMoreClicksByHours($clientId, $campaign, $accessPoint, $dateFrom, $dateTo);

		$categories = [];
		$values = [];

		foreach ($records as $data) {
			array_push($categories, DateTimeHelper::formatHourPreProcessedReport($data['hour']));
			array_push($values, (int)$data['quantity']);
		}

		return [
			'categories' => $categories,
			'values' => $values
		];
	}

	public function campaignCTADetailAction(Request $request)
	{
		$client = $this->getLoggedClient();
		$user = $this->controllerHelper->getUser();
		$traceHeaders = TracerHeaders::from($request);
		$consent = $this->getConsentGateway->get($client, 'pt_BR', $traceHeaders);
		PlanAssert::checkOrThrow($client, Plan::PRO);

		$params = [];

		if ($request->isMethod('GET')) {
			$filterParams = $request->query->all();

			if ($filterParams) {
				foreach ($filterParams['campaignReportFilter'] as $key => $value) {
					$params[$key] = $value;
				}
			}
		}

		$campaign = $this->em
			->getRepository("DomainBundle:Campaign")
			->findOneById($request->get('id'));

		$preLoginBanner = $this
			->callToActionRepository
			->getMostClickedByCampaign(
				$request->get('id'),
				$params,
				CampaignViews::STEP_PRE
			);

		if ($preLoginBanner) {
			$preLoginBanner = $preLoginBanner[0]['quantity'];
		}

		$posLoginBanner = $this
			->callToActionRepository
			->getMostClickedByCampaign(
				$request->get('id'),
				$params,
				CampaignViews::STEP_POS
			);

		if ($posLoginBanner) {
			$posLoginBanner = $posLoginBanner[0]['quantity'];
		}

		return $this->render(
			'AdminBundle:Report:campaignCallToActionDetail.html.twig',
			[
				'campaign' => $campaign,
				'preLoginBanner' => $preLoginBanner,
				'posLoginBanner' => $posLoginBanner,
				'preLoginType' => CampaignViews::STEP_PRE,
				'posLoginType' => CampaignViews::STEP_POS,
				'consent' => $consent
			]
		);
	}

	/**
	 * @param Request $request
	 * @return Response|null
	 * @throws AuditException
	 * @throws ClientPlanNotFoundException
	 * @throws NotAuthorizedPlanException
	 */
	public function campaignCTAGuestsAction(Request $request)
	{
        if (
            $this->authorizationChecker->isGranted('ROLE_USER_LIMITED') ||
            $this->authorizationChecker->isGranted('ROLE_MARKETING_LIMITED') ||
            $this->authorizationChecker->isGranted('ROLE_USER_BASIC')
        ) {
            throw new AccessDeniedException(('Unauthorized access!'));
        }
		$client = $this->getLoggedClient();
		$user = $this->controllerHelper->getUser();
		PlanAssert::checkOrThrow($client, Plan::PRO);

        $activeLegalBase = $this->legalBaseManager->getActiveLegalBase($client);


		$filterParams = $request->query->all();

		$params = [
			'campaignId' => $filterParams['id'],
			'type' => $filterParams['type'],
		];

		$dateFrom = array_key_exists('params', $filterParams)
			? $filterParams['params']['campaignReportFilter']['date_from']
			: '';

		$dateTo = array_key_exists('params', $filterParams)
			? $filterParams['params']['campaignReportFilter']['date_to']
			: '';

		if (!$dateFrom) {
			$dateFrom = new \DateTime("NOW -30 days");
			$dateFrom = $dateFrom->format('d/m/Y');
		}

		if (!$dateTo) {
			$dateTo = new \DateTime("NOW");
			$dateTo = $dateTo->format('d/m/Y');
		}

		$params['date_from'] = $dateFrom;
		$params['date_to'] = $dateTo;

		$records = $this
			->callToActionRepository
			->getMostClickedByGuest($params);

		$results = [];

        $guestFilter = [];
        if ($activeLegalBase->getLegalKind()->getKey() == LegalKinds::TERMO_CONSENTIMENTO) {
            $guestFilter['hasConsentRevoke']= [
                '$ne' => true
            ];
        }

		foreach ($records as $record) {
            $guestFilter['mysql'] = (int)$record['guest'];
			$guest = $this->mongo
				->getRepository('DomainBundle:Guest\Guest')
				->findOneBy($guestFilter);

            if (is_null($guest)) continue;
                // Audit
                $event = $this
                    ->auditor
                    ->newEvent()
                    ->withClient($client->getId())
                    ->withSource(Kinds::userAdmin(), $user->getId())
                    ->withType(Events::view())
                    ->onTarget(Kinds::guest(),is_null($guest) ? "N/D" : $guest->getMysql())
                    ->addDescription(AuditEvent::PT_BR, 'Usuário visualizou visitante na listagem do relatório de call to action')
                    ->addDescription(AuditEvent::EN_US, 'User viewed visitor in the call to action report listing')
                    ->addDescription(AuditEvent::ES_ES, 'Visitante visto por el usuario en la lista del informe de llamada a la acción');
                $this->auditor->push($event);

                $record['guestId'] = $guest ? $guest->getId() : null;
                $record['guest'] = $guest ? $guest->getProperties()[$guest->getLoginField()] : 'Não informado';
                $record['type'] = ($record['type'] == 1) ? 'Pré-Login' : 'Pós-Login';


                $ap = $this->em
                    ->getRepository('DomainBundle:AccessPoints')
                    ->getAccessPointByIdentifier($record['ap_mac_address'], $this->getLoggedClient());

                $accessPoint = $ap ? $ap[0]->getFriendlyName() : 'Não informado';

                $record['accessPoint'] = $accessPoint;

                array_push($results, $record);
		}

		$traceHeaders = TracerHeaders::from($request);
		$consent = $this->getConsentGateway->get($client, 'pt_BR', $traceHeaders);
		return $this->render(
			'AdminBundle:Report:campaignCallToActionGuests.html.twig',
			[
				'results' => $results,
				'campaign' => $filterParams['id'],
				'type' => $filterParams['type'],
				'consent' => $consent
			]
		);
	}

	/**
	 * @param Request $request
	 * @param int $page
	 * @return Response|null
	 * @throws ClientPlanNotFoundException
	 * @throws NotAuthorizedPlanException
	 * @throws AuditException
	 * @throws Exception
	 */
	public function onlineUserAction(Request $request, $page = 1)
	{
        if (
            $this->authorizationChecker->isGranted('ROLE_USER_LIMITED') ||
            $this->authorizationChecker->isGranted('ROLE_MARKETING_LIMITED')
        ) {
            throw new AccessDeniedException(('Unauthorized access!'));
        }
		$customFields = $this->customFieldsService->getCustomFields();
		$client = $this->getLoggedClient();
		$user = $this->controllerHelper->getUser();
		$traceHeaders = TracerHeaders::from($request);
		$consent = $this->getConsentGateway->get($client, 'pt_BR', $traceHeaders);
		$filters = [];

		$filter = $this->controllerHelper->createForm(OnlineUsersFilterType::class, null, [
			"attr" => [
				"client" => $client->getId()
			]
		]);

		PlanAssert::checkOrThrow($client, Plan::PRO);

		$filter->handleRequest($request);
		if ($filter->isValid()) {
			$ap = $filter->get("access_point")->getData();
			if (!empty($ap)) {
				$filters["access_point"] = $ap->getFriendlyName();
			}
		}

		$filters = [
			'maxReportLinesPoc' => ($client->getStatus() == \Wideti\DomainBundle\Entity\Client::STATUS_POC)
				? $this->maxReportLinesPoc : null,
			'filters' => $filters
		];

		$loginField = $this->customFieldsService->getLoginField()[0];
		$guests = $this->radacctReportService->getOnlineGuests($client, $filters);
		$pagination = $this->paginator->paginate($guests, $page, 20);

		// Auditoria
		foreach ($guests as $g) {
			$event = $this->auditor
				->newEvent()
				->withClient($client->getId())
				->withSource(Kinds::userAdmin(), $user->getId())
				->onTarget(Kinds::guest(), $g['guest_mysql'])
				->withType(Events::view())
				->addDescription(AuditEvent::PT_BR, 'Usuário visualizou visitante na listagem de visitantes online')
				->addDescription(AuditEvent::EN_US, 'User viewed visitor in the online visitor list')
				->addDescription(AuditEvent::ES_ES, 'Visitante visto por el usuario en la lista de visitantes en línea');
			$this->auditor->push($event);
		}

		return $this->render(
			'AdminBundle:Report:onlineUsers.html.twig',
			[
				'customFieldNames' => $customFields,
				'loginField' => $loginField,
				'client' => $client,
				'maxReportLines' => $this->maxReportLinesPoc,
				'pagination' => $pagination,
				'filter' => $filter->createView(),
				'reportType' => ReportType::ONLINE_GUEST,
				'consent' => $consent,
				'enableDisconnectGuest' => $this->moduleService->modulePermission('disconnect_guest')
			]
		);
	}

	/**
	 * @param Request $request
	 * @param int $page
	 * @return Response|null
	 * @throws ClientPlanNotFoundException
	 * @throws NotAuthorizedPlanException
	 * @throws AuditException
	 * @throws Exception
	 */
	public function heatMapAction(Request $request, $page = 1)
	{
		if (!$this->moduleService->modulePermission('heatmap')) {
            return $this->render(
                'AdminBundle:Admin:modulePermission.html.twig'
            );
        }
		$client = $this->getLoggedClient();

		PlanAssert::checkOrThrow($client, Plan::PRO);

		$accessByAp = $this->radacctReportService->getTotalAccessByAp($client);

		return $this->render(
			'AdminBundle:Report:heatMap.html.twig',
			[
				'accessByAp' => $accessByAp
			]
		);
	}

	public function disconnectGuestAction(Request $request) {

		$enableDisconnectGuest = $this->moduleService->modulePermission('disconnect_guest');

		if (!$enableDisconnectGuest) {
			return new JsonResponse(
				"Visitor disconnection module not enabled for this client.",
				Response::HTTP_UNAUTHORIZED
			);
		}

		$content = $request->request->all();

		$this->disconnectGuestProducer->publishRequest($content);

		return new JsonResponse("Disconnect request sent to RabbitMQ", Response::HTTP_OK);
	}

	public function mostVisitedHoursAction(Request $request)
	{
		$date_from = date_format(new \DateTime("NOW -30 days"), 'Y-m-d 00:00:00');
		$date_to = date_format(new \DateTime("NOW"), 'Y-m-d 23:59:59');
		$access_point = [];

		if ($request->get('dashboard') == 1) {
			$date_from = date_format(new \DateTime("NOW -6 days"), 'Y-m-d 00:00:00');
		}

		$filter = $this
			->controllerHelper
			->createForm(DateFromToType::class, null, [
				"attr" => [
					"dashboard" => $request->get('dashboard'),
					"client" => $this->getLoggedClient()->getId()
				]
			]);

		$filter->handleRequest($request);

		if ($filter->isValid()) {
			$dataFilter = $filter->getData();
			$access_point = $dataFilter['access_point'];

			if ($access_point) {
				$accessPoints = $this->em
					->getRepository('DomainBundle:AccessPoints')
					->getManyAccessPointById($access_point);

				$access_point = [];

				foreach ($accessPoints as $aps) {
					array_push(
						$access_point,
						["term" => ["friendlyName" => $aps['friendlyName']]]
					);
				}
			}

			$date_from = date_format($dataFilter['date_from'], 'Y-m-d 00:00:00');

			if (null == !$dataFilter['date_to']) {
				$date_to = date_format($dataFilter['date_to']->setTime(23, 59, 59), 'Y-m-d 23:59:59');
			}
		}

		$dateDiff = date_diff(new \DateTime($date_from), new \DateTime($date_to));

		if ($dateDiff->days > 31) {
			$date_from = date_format(new \DateTime("NOW -30 days"), 'Y-m-d 00:00:00');
			$date_to = date_format(new \DateTime("NOW"), 'Y-m-d 23:59:59');
		}

		$visitsAndRegistrations = $this->radacctReportService->mostAccessesHours(
			$this->getLoggedClient(),
			[
				'date_from' => $date_from,
				'date_to' => $date_to,
				'filtered' => true
			],
			$access_point
		);

		$records = ['visits' => [], 'registrations' => []];
		$i = 1;

		foreach ($visitsAndRegistrations['access_by_hour_visits']['buckets'] as $data) {
			if ($i <= 10) {
				array_push($records['visits'], $data);
			}

			$i++;
		}

		reset($visitsAndRegistrations);
		$i = 1;

		foreach ($visitsAndRegistrations['access_by_hour_registrations']['buckets'] as $data) {
			if ($i <= 10) {
				array_push($records['registrations'], $data);
			}
			$i++;
		}

		reset($visitsAndRegistrations);

		$visitsGraph = [];
		$registrationsGraph = [];

		foreach ($records['visits'] as $data) {
			$visitsGraph[DateTimeHelper::formatHourPreProcessedReport($data['key'])] = $data['totalVisits']['value'];
		}

		reset($records);

		foreach ($records['registrations'] as $data) {
			$registrationsGraph[DateTimeHelper::formatHourPreProcessedReport($data['key'])] = $data['totalRegistrations']['value'];
		}

		reset($records);

		$categories = [
			'visitsGraph' => array_keys($visitsGraph),
			'registrationsGraph' => array_keys($registrationsGraph)
		];

		$values = [
			'visitsGraph' => array_values($visitsGraph),
			'registrationsGraph' => array_values($registrationsGraph)
		];

		return $this->render(
			'AdminBundle:Report:mostVisitedHours.html.twig',
			[
				'records' => $records,
				'filter' => $filter->createView(),
				'categories' => $categories,
				'values' => $values,
				'reportType' => ReportType::MOST_VISITED_HOURS
			]
		);
	}

	public function recordsPerDayAction(Request $request)
	{
		$date_from = date_format(new \DateTime("NOW -30 days"), 'Y-m-d 00:00:00');
		$date_to = date_format(new \DateTime("NOW"), 'Y-m-d 23:59:59');
		$access_point = [];

		if ($request->get('dashboard') == 1) {
			$date_from = date_format(new \DateTime("NOW -6 days"), 'Y-m-d 00:00:00');
		}

		$filter = $this
			->controllerHelper
			->createForm(DateFromToWithLimitType::class, null, [
				"attr" => [
					"dashboard" => $request->get('dashboard'),
					"client" => $this->getLoggedClient()->getId()
				]
			]);

		$filter->handleRequest($request);

		if ($filter->isValid()) {
			$dataFilter = $filter->getData();
			$access_point = $dataFilter['access_point'];

			if ($access_point) {
				$accessPoints = $this->em
					->getRepository('DomainBundle:AccessPoints')
					->getManyAccessPointById($access_point);

				$access_point = [];

				foreach ($accessPoints as $aps) {
					array_push(
						$access_point,
						["term" => ["friendlyName" => $aps['friendlyName']]]
					);
				}
			}

			$date_from = date_format($dataFilter['date_from'], 'Y-m-d 00:00:00');

			if (null == !$dataFilter['date_to']) {
				$date_to = date_format($dataFilter['date_to']->setTime(23, 59, 59), 'Y-m-d 23:59:59');
			}
		}

		$dateDiff = date_diff(new \DateTime($date_from), new \DateTime($date_to));

		if ($dateDiff->days > 31) {
			$date_from = date_format(new \DateTime("NOW -30 days"), 'Y-m-d 00:00:00');
			$date_to = date_format(new \DateTime("NOW"), 'Y-m-d 23:59:59');
		}

		$period = new \DatePeriod(
			new \DateTime($date_from),
			new \DateInterval("P1D"),
			new \DateTime($date_to)
		);

		$totalVisitsAndRegisters = $this->radacctReportService->processVisitsAndRecordsPerDay(
			$this->getLoggedClient(),
			[
				'date_from' => $date_from,
				'date_to' => $date_to,
				'filtered' => true
			],
			$access_point
		);

		$totalList = [];
		$totalGraph = [];

		foreach ($period as $date) {
			$totalGraph[$date->format("d/m")] = 0;
		}

		$perDayGraph = DateTimeHelper::daysOfWeek();
		$perDayList = [];

		foreach ($totalVisitsAndRegisters as $data) {
			$totalRegisters = $data['totalRegistrations']['value'];

			$notFormatted = explode('/', $data['key_as_string']);
			$dateFormatted = date('Y') . "-{$notFormatted[1]}-{$notFormatted[0]}";
			$dayOfWeek = date('w', strtotime($dateFormatted));
			$perDayGraph[$dayOfWeek] += (int)$totalRegisters;

			if ($totalRegisters > 0) {
				$totalList[$data['key_as_string']]['total'] = (int)$totalRegisters;
				$totalList[$data['key_as_string']]['period'] = $dateFormatted;
			}

			$totalGraph[$data['key_as_string']] = (int)$totalRegisters;
		}

		foreach ($perDayGraph as $key => $value) {
			$perDayList[$key] = is_int($value)
				? $value
				: 0;
		}

		$categories = [
			'totalGraph' => array_keys($totalGraph),
			'perDayGraph' => array_values(DateTimeHelper::daysOfWeek())
		];

		$values = [
			'totalGraph' => array_values($totalGraph),
			'perDayGraph' => array_values($perDayGraph)
		];

		return $this->render(
			'AdminBundle:Report:registerPerDay.html.twig',
			[
				'period' => $period,
				'totalList' => $totalList,
				'perDayList' => $perDayList,
				'filter' => $filter->createView(),
				'categories' => $categories,
				'values' => $values,
				'reportType' => ReportType::RECORDS_PER_DAY
			]
		);
	}

	/**
	 * @param Request $request
	 * @param int $page
	 * @return Response
	 * @throws ClientPlanNotFoundException
	 * @throws NotAuthorizedPlanException
	 * @throws AuditException
	 */
	public function guestsAction(Request $request, $page = 1)
	{
        if (
            $this->authorizationChecker->isGranted('ROLE_USER_LIMITED') ||
            $this->authorizationChecker->isGranted('ROLE_MARKETING_LIMITED') ||
            $this->authorizationChecker->isGranted('ROLE_USER_BASIC')
        ) {
            throw new AccessDeniedException(('Unauthorized access!'));
        }
		$client = $this->getLoggedClient();
		$traceRequest = TracerHeaders::from($request);
		$consent = $this->getConsentGateway->get($client, 'pt_BR', $traceRequest);

		PlanAssert::checkOrThrow($client, Plan::PRO);

		$client = $this->getLoggedClient();
		$dateFrom = new \DateTime("NOW -30 days");
		$dateTo = new \DateTime("NOW");

		$options['attr']['client'] = $client->getId();
		$options['attr']['unique'] = $request->get('unique_guests');
		$options['attr']['recurring'] = $request->get('returning_guests');

		$filterForm = $this->controllerHelper->createForm(
			GuestReportFilterType::class,
			null,
			$options
		);
		$filterForm->handleRequest($request);
		$filters = [];

		$filters['recurrence'] = $request->get('unique_guests') ? 'unique' : ($request->get('returning_guests') ? 'recurring' : '');

		if ($filterForm->isValid()) {
			$filters = $filterForm->getData();
			/** @var \DateTime $dateFrom */
			$dateFrom = $filters['date_from'] ?: $dateFrom;
			/** @var \DateTime $dateTo */
			$dateTo = $filters['date_to'] ?: $dateTo;
		}

		$dateFrom->setTime(0, 0, 0);
		$dateTo->setTime(23, 59, 59);

		$pocStatus = \Wideti\DomainBundle\Entity\Client::STATUS_POC;
		$inPoc = ($client->getStatus() == $pocStatus);

		$filter = GuestAccessReportFilterBuilder::getBuilder()
			->withFieldToFilter(isset($filters['range_by']) ? $filters['range_by'] : 'lastAccess')
			->withDateFrom($dateFrom)
			->withDateTo($dateTo)
			->withRecurrence(isset($filters['recurrence']) ? $filters['recurrence'] : null)
			->build();

		$pageGuestsIds = $this->guestService->retrieveGuestsIds($client, $dateFrom, $dateTo, $filter);

		$pageLimit = $inPoc ? $this->maxReportLinesPoc : 10;
		$guestIndexStart = ($page - 1) * $pageLimit;

		$slicedGuestsIds = array_slice($pageGuestsIds, $guestIndexStart, $pageLimit);
		$guestsIds = array_map(function($guest) {
			return $guest['key'];
		}, $slicedGuestsIds);

		$results = $this
			->guestService
			->getGuestInformationFromAccessDataReport($client, $dateFrom, $dateTo, $filter, $guestsIds);

        $guestsConsentRevokeId  = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->getConsentRevokesId();
        $guestsConsentRevokeId = $guestsConsentRevokeId->toArray();
        $idsRevoked = [];
        foreach($guestsConsentRevokeId as $g) {
            $idsRevoked[] = $g->getMysql();
        }

		//audit
		$user = $this->controllerHelper->getUser();
		foreach ($results as  $k => $r) {
            if (in_array($r->getUserNameId(), $idsRevoked)) {
                unset($results[$k]);
                continue;
            }
            $event = $this->auditor
				->newEvent()
				->withClient($client->getId())
				->withSource(Kinds::userAdmin(), $user->getId())
				->onTarget(Kinds::guest(), $r->getUserNameId())
				->withType(Events::view())
				->addDescription(AuditEvent::PT_BR, 'Usuário visualizou visitante a partir o relatório de visitantes')
				->addDescription(AuditEvent::EN_US, 'User viewed visitor from the visitor report')
				->addDescription(AuditEvent::ES_ES, 'Visitante visto por el usuario desde el informe de visitantes');
			$this->auditor->push($event);
		}

		$loginField = $this->customFieldsService->getLoginField()[0];
		$pagination = $this->paginator->paginate($pageGuestsIds, $page, $pageLimit);

		return $this->render(
			'AdminBundle:Report:guests.html.twig',
			[
				'client' => $client,
				'maxReportLines' => $this->maxReportLinesPoc,
				'inPoc' => $inPoc,
				'loginField' => $loginField,
				'count' => count($pageGuestsIds),
				'entity' => $results,
				'date_from' => $dateFrom->format('Y-m-d H:i:s'),
				'date_to' => $dateTo->format('Y-m-d H:i:s'),
				'pagination' => $pagination,
				'filter' => $filterForm->createView(),
				'reportType' => ReportType::GUESTS,
				'consent' => $consent
			]
		);
	}

	/**
	 * @param Request $request
	 * @param int $page
	 * @return Response|null
	 * @throws AuditException
	 * @throws ClientPlanNotFoundException
	 * @throws NotAuthorizedPlanException
	 */
	public function birthdaysAction(Request $request, $page = 1)
	{
		$client = $this->getLoggedClient();
		PlanAssert::checkOrThrow($client, Plan::PRO);

		$client = $this->getLoggedClient();
		$filter = date("m");

		$filterForm = $this->controllerHelper->createForm(MonthFilterType::class, null);
		$filterForm->handleRequest($request);

		if ($filterForm->isValid()) {
			$dataFilter = $filterForm->getData();
			$filter = $dataFilter['month'];
		}

		$pocStatus = \Wideti\DomainBundle\Entity\Client::STATUS_POC;

		$filters = [
			'maxReportLinesPoc' => ($client->getStatus() == $pocStatus) ? $this->maxReportLinesPoc : null,
			'filters' => $filter
		];

		$customFields = $this->customFieldsService->getCustomFields();
		$guests = $this->mongo
			->getRepository('DomainBundle:Guest\Guest')
			->getGuestsByBirthDate($filters, $customFields);

		//Audit
		$user = $this->controllerHelper->getUser();
		foreach ($guests as $g) {
			$event = $this->auditor
				->newEvent()
				->withClient($client->getId())
				->withSource(Kinds::userAdmin(), $user->getId())
				->onTarget(Kinds::guest(), $g['mysql'])
				->withType(Events::view())
				->addDescription(AuditEvent::PT_BR, 'Usuário visualizou visitante a partir dos aniversariantes do mês')
				->addDescription(AuditEvent::EN_US, 'User viewed visitor from birthdays of the month')
				->addDescription(AuditEvent::ES_ES, 'Visitante visto por el usuario desde los cumpleaños del mes');
			$this->auditor->push($event);
		}

		$pagination = $this->paginator->paginate($guests, $page, 10);

		return $this->render(
			'AdminBundle:Report:birthdays.html.twig',
			[
				'client' => $client,
				'maxReportLines' => $this->maxReportLinesPoc,
				'count' => count($guests),
				'guests' => $guests,
				'pagination' => $pagination,
				'filter' => $filterForm->createView(),
				'reportType' => ReportType::BIRTHDAYS,
				'customFieldNames' => $customFields
			]
		);
	}

	public function auditLogAction(Request $request, $page = 1)
	{
		$client = $this->getLoggedClient();
		PlanAssert::checkOrThrow($client, Plan::PRO);
	
        if (
            $this->authorizationChecker->isGranted('ROLE_USER_LIMITED') ||
            $this->authorizationChecker->isGranted('ROLE_MARKETING_LIMITED') ||
            $this->authorizationChecker->isGranted('ROLE_USER_BASIC')
        ) {
            throw new AccessDeniedException(('Unauthorized access!'));
        }

		$dateFrom = new \DateTime("NOW -30 days");
		$dateTo = new \DateTime("NOW");

		$options['em'] = $this->em;
		$options['client'] = $client;
		$options['attr']['client'] = $client->getId();
		$options['attr']['event_type'] = $request->get('event_type');
	
		$filterForm = $this->controllerHelper->createForm(
			AuditLogFilterType::class,
			null,
			$options
		);
		$filterForm->handleRequest($request);
		$filters = [];
	
		if ($filterForm->isValid()) {
			$filters = $filterForm->getData();
			/** @var \DateTime $dateFrom */
			$dateFrom = $filters['date_from'] ?: $dateFrom;
			/** @var \DateTime $dateTo */
			$dateTo = $filters['date_to'] ?: $dateTo;
		}
	
		$eventType = isset($filters['event_type']) ? $filters['event_type'] : null;
		$user = isset($filters['user']) ? $filters['user'] : null;

		$dateFrom->setTime(0, 0, 0);
		$dateTo->setTime(23, 59, 59);

		$userEntity = $this->controllerHelper->getUser();
		$companyAdminRoles = [
			$userEntity::ROLE_SUPORT_LIMITED,
			$userEntity::ROLE_SUPER_ADMIN,
			$userEntity::ROLE_MANAGER,
		];

		$filterCompanyAdmins = false;
		foreach ($userEntity->getRoles() as $role => $value) {
			if (!in_array($value->getId(), $companyAdminRoles)) {
				$filterCompanyAdmins = true;
			}
		}
	
		$pageLimit = 25;
		$auditEvents = $this->em->getRepository('DomainBundle:AuditLog')
		->findWithFilters(
			$client->getId(),
			$dateFrom,
			$dateTo,
			$eventType,
			$user,
			$filterCompanyAdmins
		);
	

		$pagination = $this->paginator->paginate(
			$auditEvents,
			$page,
			$pageLimit
		);
	
		return $this->render(
			'AdminBundle:Report:auditLog.html.twig',
			[
				'client' => $client,
				'pagination' => $pagination,
				'filter' => $filterForm->createView(),
				'auditEvents' => $auditEvents,
				'date_from' => $dateFrom->format('Y-m-d H:i:s'),
				'date_to' => $dateTo->format('Y-m-d H:i:s')
			]
		);
	}

	public function batchReportProcess(Request $request)
	{
		$rawMessage = Message::fromRawPostData();
		$message = $rawMessage->toArray();
		$data = $message['Message'];

		if ($message['Type'] == 'SubscriptionConfirmation') {
			$guzzle = new Client();
			$guzzle->request("GET", $message['SubscribeURL']);
			header("Status: 200");
			exit;
		}

		$arrayData = explode("|", $data);

		list($reportType, $filter, $clientId, $username, $format, $charset, $userId) = $arrayData;

		if (empty($clientId) || $clientId == 0) {
			throw new ClientNotFoundException("Id do cliente é inválido id: " . $clientId);
		}

		$filter = json_decode($filter, true);

		$client = $this->em
			->getRepository('DomainBundle:Client')
			->find($clientId);

		$user = $this->verifyProfileAndLoadUser($userId, $client);

		if (empty($user)) {
			throw new UnauthorizedHttpException("Not possible load user on batch report request: $data");
		}

		if (empty($client)) {
			throw new ClientNotFoundException("Cliente id: " . $clientId . " não foi encontrado");
		}

		$this
			->reportService
			->processBatchReport($reportType, $filter, $client, $user, $username, $format);

		return new Response("Report created", 201);
	}

	/**
	 * @param $userId
	 * @param \Wideti\DomainBundle\Entity\Client $client
	 * @return Users
	 */
	private function verifyProfileAndLoadUser($userId, \Wideti\DomainBundle\Entity\Client $client)
	{
		// Carrega se usuário for administrador de um painel
		$user = $this->getClientAdminUser($userId, $client);
		if ($user) {
			return $user;
		}

		// Carrega se o usuário for um funcionário da WSpot
		$user = $this->getManagerUser($userId);
		if ($user) {
			return $user;
		}

		// Carrega usuário se ele for um spot manager e possuir esse cliente em sua lista
		$user = $this->getSpotManagerUser($userId, $client);
		if ($user) {
			return $user;
		}

		return null;

	}

	private function getSpotManagerUser($userId, \Wideti\DomainBundle\Entity\Client $client)
	{
		// Verifica se o spot manager tem autorização para exportar para esse cliente
		$spotUser = $this->em->getRepository("DomainBundle:SpotUser")
			->findOneBy([
				'userId' => $userId,
				'clientId' => $client->getId()
			]);

		if ($spotUser != null) {
			return $this
				->em
				->getRepository("DomainBundle:Users")
				->findOneBy([
					'id' => $userId,
					'spotManager' => true
				]);
		}

		return null;
	}

	/**
	 * @param $userId
	 * @return Users|null
	 */
	private function getManagerUser($userId) {

		$roleManager = $this->em
			->getRepository('DomainBundle:Roles')
			->findOneBy([
				'role' => 'ROLE_MANAGER'
			]);

		return $this->em
			->getRepository('DomainBundle:Users')
			->findOneBy([
				'id' => $userId,
				'role' => $roleManager
			]);
	}

	/**
	 * @param $userId
	 * @param \Wideti\DomainBundle\Entity\Client $client
	 * @return Users|null
	 */
	private function getClientAdminUser($userId, \Wideti\DomainBundle\Entity\Client $client)
	{
		return $this->em
			->getRepository('DomainBundle:Users')
			->findOneBy([
				'id' => $userId,
				'client' => $client
			]);
	}

	public function getAvailableReportsOnS3(Request $request)
	{
		$folder = $request->get('folder');

		if ($folder) {
			$files = $this->reportService->getAvailableReportsOnS3($folder);
			return new JsonResponse($files, 200);
		}

		return new JsonResponse(false, 400);
	}

    public function generateSignedUrl(Request $request)
    {
        $folder = $request->get('folder');
        $filename = $request->get('filename');

        if ($folder && $filename) {
            $url = $this->reportService->generateSignedS3Url($folder, $filename);

            return new JsonResponse($url, 200);
        }

        return new JsonResponse(false, 400);
	}

    public function setAuthorizationChecker(AuthorizationChecker $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }
}
 