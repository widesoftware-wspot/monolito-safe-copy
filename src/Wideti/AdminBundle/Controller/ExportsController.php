<?php

namespace Wideti\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wideti\DomainBundle\Analytics\Events\EventType;
use Wideti\DomainBundle\Gateways\Consents\GetConsentGateway;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\AuditException;
use Wideti\DomainBundle\Service\AuditLogs\Auditor;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\DomainBundle\Service\Jaeger\TracerHeaders;
use Wideti\DomainBundle\Service\Report\ReportService;
use Wideti\DomainBundle\Service\Report\ReportServiceAware;
use Wideti\DomainBundle\Service\Report\ReportType;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\DomainBundle\Service\AuditLogInternal\AuditLogService;


class ExportsController
{
	use ReportServiceAware;
	use SessionAware;
	use EntityManagerAware;

	/**
	 * @var AdminControllerHelper
	 */
	private $controllerHelper;
	/**
	 * @var Auditor
	 */
	private $auditor;
	/**
	 * @var GetConsentGateway
	 */
	private $getConsentGateway;

    /**
     * @var auditLogService
     */
    private $auditLogService;

	/**
	 * ExportsController constructor.
	 * @param AdminControllerHelper $controllerHelper
	 * @param Auditor $auditor
	 * @param GetConsentGateway $getConsentGateway
	 */
	public function __construct(
		AdminControllerHelper $controllerHelper,
		Auditor $auditor,
		GetConsentGateway $getConsentGateway,
        AuditLogService $auditLogService
	)
	{
		$this->controllerHelper         = $controllerHelper;
		$this->getConsentGateway        = $getConsentGateway;
		$this->auditor                  = $auditor;
        $this->auditLogService          = $auditLogService;
	}

	/**
	 * @param Request $request
	 * @return string|RedirectResponse|Response
	 * @throws AuditException
	 */
	public function exportOnlineGuests(Request $request)
	{
		$client = $this->getLoggedClient();
		$user = $this->controllerHelper->getUser();
		$traceHeaders = TracerHeaders::from($request);
		$consent = $this->getConsentGateway->get($client, 'pt_BR', $traceHeaders);

		$event = $this->auditor
			->newEvent()
			->withClient($client->getId())
			->withSource(Kinds::userAdmin(), $user->getId())
			->withType(Events::accept())
			->onTarget(Kinds::onlineGuestReport(), 'online_guest_report')
			->addDescription(AuditEvent::PT_BR, 'Usuário aceitou exportar relatório de visitantes online e respeitar o termo de consentimento')
			->addDescription(AuditEvent::EN_US, 'User accepted to export visitor report online and respect the consent form')
			->addDescription(AuditEvent::ES_ES, 'El usuario aceptó exportar el informe de visitantes en línea y respetar el término de consentimiento.');
		if ($consent->getHasError()) {
			$event->addContext('consent', 'Error on retrieve consent information: ' . $consent->getError()->getMessage());
		} else {
			$event->addContext('consent_id', $consent->getId());
			$event->addContext('consent_version', $consent->getVersion());
		}
		$this->auditor->push($event);

		$params = [];
		$requestParams = $request->query->all();

		$filterParams = [];

		$charset = $requestParams['charset'];
		$format = $requestParams['fileFormat'];

		if (array_key_exists('filters', $requestParams)) {
			parse_str($requestParams['filters'], $filterParams);
		}

		if ($filterParams) {
			if (reset($filterParams) != '' && array_key_exists('access_point', reset($filterParams))) {
				$params['access_point'] = reset($filterParams)['access_point'];
			}
		}

		$response = $this
			->reportService
			->processReport(ReportType::ONLINE_GUEST, $params, $client, $format, $charset);

		if ($response == 'empty') {
			return new RedirectResponse(
				$this->controllerHelper->generateUrl('admin_online_user_report')
			);
		}

        $this->auditLogService->createAuditLog(
            'export-online-guests',
            Events::create()->getValue(),
            null,
            true
        );

		return $response;
	}

	/**
	 * @param Request $request
	 * @return string|RedirectResponse|Response
	 * @throws AuditException
	 */
	public function accessHistoric(Request $request)
	{

		$client = $this->getLoggedClient();
		$user = $this->controllerHelper->getUser();
		$traceRequest = TracerHeaders::from($request);
		$consent = $this->getConsentGateway->get($client, 'pt_BR', $traceRequest);

		$event = $this
			->auditor
			->newEvent()
			->withClient($client->getId())
			->withSource(Kinds::userAdmin(), $user->getId())
			->withType(Events::accept())
			->onTarget(Kinds::accessHistoricReport(), 'access_historic_report')
			->addDescription(AuditEvent::PT_BR, 'Usuário aceitou exportar relatório de histórico de acesso e usar de acordo com seus termos de consentimento')
			->addDescription(AuditEvent::EN_US, 'User accepted to export access history report and use according to their consent terms')
			->addDescription(AuditEvent::ES_ES, 'El usuario aceptó exportar el informe del historial de acceso y usarlo de acuerdo con sus términos de consentimiento');
		if ($consent->getHasError()) {
			$event->addContext('consent', 'Fail on retrieve consent information on access historic export: ' . $consent->getError()->getMessage());
		} else {
			$event->addContext('consent_id', $consent->getId());
			$event->addContext('consent_version', $consent->getVersion());
		}
		$this->auditor->push($event);

		$requestParams = $request->query->all();
		$params = [];
		$filterParams = [];
		$charset = $requestParams['charset'];
		$fileFormat = $requestParams['fileFormat'];

		if (array_key_exists('filters', $requestParams)) {
			parse_str($requestParams['filters'], $filterParams);
		}

		if ($filterParams && array_key_exists('reportsFilter', $filterParams)) {
			if (reset($filterParams) != '' && array_key_exists('filter', reset($filterParams))) {
				$params['filter'] = reset($filterParams)['filter'];
			}

			foreach ($filterParams['reportsFilter'] as $key => $value) {
				$params[$key] = $value;
			}
		}

		$reportResponse = $this
			->reportService
			->processReport(ReportType::ACCESS_HISTORIC, $params, $client, $fileFormat, $charset);

		$url = $this->controllerHelper->generateUrl('admin_relatorio_historico');

		if ($reportResponse == 'batch') {
			$this->session->getFlashBag()->add('export', true);
			$urlParams = ReportService::generateUrlParams($params, "reportsFilter");
			$url = $this->controllerHelper->generateUrl('admin_relatorio_historico', $urlParams);
			return new RedirectResponse($url);
		} elseif ($reportResponse == 'empty') {
			$this->session->getFlashBag()->add(
				'notice',
				'Não existem informações à serem exportadas no período selecionado.'
			);
			return new RedirectResponse($url);
		}

        $this->auditLogService->createAuditLog(
            'export-historic',
            Events::create()->getValue(),
            null,
            true
        );

		return $reportResponse;
	}

	public function downloadUpload(Request $request)
	{
		$client = $this->getLoggedClient();
		$requestParams = $request->query->all();
		$params = [];
		$filterParams = [];
		$charset = $requestParams['charset'];
		$fileFormat = $requestParams['fileFormat'];

		if (array_key_exists('filters', $requestParams)) {
			parse_str($requestParams['filters'], $filterParams);
		}

		if ($filterParams && array_key_exists('downloadUploadFilter', $filterParams)) {
			if (reset($filterParams) != '' && array_key_exists('filter', reset($filterParams))) {
				$params['filter'] = reset($filterParams)['filter'];
			}

			foreach ($filterParams['downloadUploadFilter'] as $key => $value) {
				$params[$key] = $value;
			}

			if (reset($filterParams) != '' && array_key_exists('access_point', reset($filterParams))) {
				if (array_key_exists('access_point', $params)) {
					foreach (reset($filterParams)['access_point'] as $key => $value) {
						$params['access_point'][] = $value;
					}
				} else {
					$params['access_point'] = reset($filterParams)['access_point'];
				}
			}
		}

		$dateFrom = date('Y');
		$dateTo = date('Y') . '||+1y';
		$params['format_range'] = "yyyy";
		$params['format_aggs'] = "yyyy-MM";
		$params['interval'] = "month";
		$params['filtered'] = false;

		if (!empty($params['access_point'])) {
			$accessPointFilter = $params['access_point'];
			$params['access_point'] = [];
			if ($accessPointFilter) {
				$accessPoints = $this->em
					->getRepository('DomainBundle:AccessPoints')
					->getManyAccessPointById($accessPointFilter);

				foreach ($accessPoints as $aps) {
					$params['access_point'][] = ["term" => ["calledstation_name" => $aps['friendlyName']]];
				}
			}
			$params['filtered'] = true;
		}

		if (!empty($params['year']) && !empty($params['month'])) {
			$dateFrom = $params['year'] . '-' . $params['month'];
			$dateTo = $params['year'] . '-' . $params['month'] . '||+1M-1d';
			$params['format_range'] = "yyyy-MM";
			$params['filtered'] = true;
		}

		$params['period'] = [
			'from' => $dateFrom,
			'to' => $dateTo
		];

        $this->auditLogService->createAuditLog(
            'export-download-upload',
            Events::create()->getValue(),
            null,
            true
        );

		return $this->reportService->processReport(
			ReportType::DOWNLOAD_UPLOAD,
			$params,
			$client,
			$fileFormat,
			$charset
		);
	}

	public function downloadUploadDetail(Request $request)
	{
		$client = $this->getLoggedClient();
		$requestParams = $request->query->all();
		$filterParams = [];
		$charset = $requestParams['charset'];
		$fileFormat = $requestParams['fileFormat'];

		if (array_key_exists('filters', $requestParams)) {
			parse_str(strstr($requestParams['filters'], 'year'), $filterParams);
		}

		$periodFrom = $filterParams['year'] . '-' . $filterParams['month'];
		$periodTo = $filterParams['year'] . '-' . $filterParams['month'] . '||+1M-1d';

		$period = [
			'from' => $periodFrom,
			'to' => $periodTo
		];

		$params['accessPoint'] = (array_key_exists('accessPoint', $filterParams)
			? $filterParams['accessPoint'] : null);
		$params['period'] = $period;
		$params['format_range'] = "yyyy-MM";
		$params['format_aggs'] = "yyyy-MM-dd";
		$params['interval'] = "day";

        $this->auditLogService->createAuditLog(
            'export-download-upload-detail',
            Events::create()->getValue(),
            null,
            true
        );

		return $this->reportService->processReport(
			ReportType::DOWNLOAD_UPLOAD_DETAIL,
			$params,
			$client,
			$fileFormat,
			$charset
		);
	}

	public function recordsPerDay(Request $request)
	{
		$format = $request->get('format');
		$client = $this->getLoggedClient();
		$dataFilter = $request->get('dateFromToWithLimitFilter');

		$date_from = date_format(new \DateTime("NOW -30 days"), 'Y-m-d 00:00:00');
		$date_to = date_format(new \DateTime("NOW"), 'Y-m-d 23:59:59');
		$params = [];
		$access_point = [];

		if (!empty($dataFilter['access_point'])) {
			$accessPoints = $this->em
				->getRepository('DomainBundle:AccessPoints')
				->getManyAccessPointById($dataFilter['access_point']);


			foreach ($accessPoints as $aps) {
				array_push(
					$access_point,
					["term" => ["friendlyName" => $aps['friendlyName']]]
				);
			}
		}

		$params['access_points'] = $access_point;

		if (!empty($dataFilter['date_from']) && !empty($dataFilter['date_from'])) {
			list($dayFrom, $monthFrom, $yearFrom) = explode("/", $dataFilter['date_from']);
			list($dayTo, $monthTo, $yearTo) = explode("/", $dataFilter['date_to']);

			$dataFilter['date_from'] = new \DateTime($yearFrom . "-" . $monthFrom . "-" . $dayFrom);
			$dataFilter['date_to'] = new \DateTime($yearTo . "-" . $monthTo . "-" . $dayTo);

			$date_from = date_format($dataFilter['date_from'], 'Y-m-d');

			if (null == !$dataFilter['date_to']) {
				$date_to = date_format($dataFilter['date_to']->setTime(23, 59, 59), 'Y-m-d');
			}
		}

		$dateDiff = date_diff(new \DateTime($date_from), new \DateTime($date_to));

		if ($dateDiff->days > 31) {
			$date_from = date_format(new \DateTime("NOW -30 days"), 'Y-m-d');
			$date_to = date_format(new \DateTime("NOW"), 'Y-m-d');
		}

		$period = new \DatePeriod(
			new \DateTime($date_from),
			new \DateInterval("P1D"),
			new \DateTime($date_to)
		);

		$params['period'] = $period;
		$params['date_from'] = $date_from;
		$params['date_to'] = $date_to;

        $this->auditLogService->createAuditLog(
            'export-records-per-day',
            Events::create()->getValue(),
            null,
            true
        );

		return $this->reportService->processReport(ReportType::RECORDS_PER_DAY, $params, $client, $format);
	}

	public function mostVisitedHours(Request $request)
	{
		$client = $this->getLoggedClient();
		$requestParams = $request->query->all();
		$params = [];
		$filterParams = [];
		$charset = $requestParams['charset'];
		$fileFormat = $requestParams['fileFormat'];

		if (array_key_exists('filters', $requestParams)) {
			$url = explode('?', $requestParams['filters']);

			if (count($url) > 1) {
				parse_str($url[1], $filterParams);
			}
		}

		if (!empty($filterParams)) {
			if (!array_key_exists('access_point', $filterParams['dateFromToFilter'])) {
				$filterParams['dateFromToFilter']['access_point'] = [];
			}

			foreach ($filterParams['dateFromToFilter'] as $key => $value) {
				$params[$key] = $value;
			}
		}

        $this->auditLogService->createAuditLog(
            'export-most-visited-hours',
            Events::create()->getValue(),
            null,
            true
        );

		return $this->reportService->processReport(ReportType::MOST_VISITED_HOURS, $params, $client, $fileFormat, $charset);
	}

	public function accessPoints(Request $request)
	{
		$client = $this->getLoggedClient();
		$requestParams = $request->query->all();
		$params = [];
		$filterParams = [];
		$charset = $requestParams['charset'];
		$fileFormat = $requestParams['fileFormat'];

		if (array_key_exists('filters', $requestParams)) {
			$url = explode('?', $requestParams['filters']);

			if (count($url) > 1) {
				parse_str($url[1], $filterParams);
			}
		}

		if (!empty($filterParams)) {
			if (!array_key_exists('access_point', $filterParams['dateFromToFilter'])) {
				$filterParams['dateFromToFilter']['access_point'] = [];
			}

			foreach ($filterParams['dateFromToFilter'] as $key => $value) {
				$params[$key] = $value;
			}
		}

		$response = $this
			->reportService
			->processReport(ReportType::ACCESS_POINTS, $params, $client, $fileFormat, $charset);

        $this->auditLogService->createAuditLog(
            'export-access-points',
            Events::create()->getValue(),
            null,
            true
        );

		return $response;
	}

	public function campaign(Request $request)
	{
		$client = $this->getLoggedClient();
		$requestParams = $request->query->all();
		$params = [];
		$filterParams = [];
		$charset = $requestParams['charset'];
		$fileFormat = $requestParams['fileFormat'];

		if (array_key_exists('filters', $requestParams)) {
			parse_str($requestParams['filters'], $filterParams);
		}

        if (!isset($filterParams['campaignReportFilter']['campaign'])) {
            $filterParams['campaignReportFilter']['campaign'] = [];
        }


		if ($filterParams && array_key_exists('campaignReportFilter', $filterParams)) {
			if (reset($filterParams) != '' && array_key_exists('filter', reset($filterParams))) {
				$params['filter'] = reset($filterParams)['filter'];
			}

			if (reset($filterParams) != '' && array_key_exists('campaign', reset($filterParams))) {
				array_push($filterParams['campaignReportFilter']['campaign'], reset($filterParams)['campaign'][0]);
			}

			if (reset($filterParams) != '' && array_key_exists('access_point', reset($filterParams))) {
				array_push($filterParams['campaignReportFilter']['access_point'], reset($filterParams)['access_point'][0]);
			}

			foreach ($filterParams['campaignReportFilter'] as $key => $value) {
				$params[$key] = $value;
			}
		}

        $this->auditLogService->createAuditLog(
            'export-campaign',
            Events::create()->getValue(),
            null,
            true
        );

		$response = $this
			->reportService
			->processReport(ReportType::CAMPAIGN, $params, $client, $fileFormat, $charset);

		return $response;
	}

	/**
	 * @param Request $request
	 * @return string|RedirectResponse|Response
	 * @throws AuditException
	 */
	public function callToAction(Request $request)
	{
		$client = $this->getLoggedClient();
		$user = $this->controllerHelper->getUser();
		$traceHeaders = TracerHeaders::from($request);
		$consent = $this->getConsentGateway->get($client, 'pt_BR', $traceHeaders);
		$requestParams = $request->query->all();
		$requestDateParams = isset($request->get('params')['params']) ?
			$request->get('params')['params']
			: [];

		// Auditor
		$event = $this
			->auditor
			->newEvent()
			->withClient($client->getId())
			->withSource(Kinds::userAdmin(), $user->getId())
			->withType(Events::accept())
			->onTarget(Kinds::callToActionReport(), 'call_to_action_report')
			->addDescription(AuditEvent::PT_BR, 'Usuário aceitou exportar visitantes via relatório call to action e usar os dados de acordo com os termos de consentimentos')
			->addDescription(AuditEvent::EN_US, 'User accepted to export visitors via call to action report and use the data according to the consent terms')
			->addDescription(AuditEvent::ES_ES, 'El usuario aceptó exportar visitantes a través del informe de llamada a la acción y utilizar los datos de acuerdo con los términos de consentimiento');
		if ($consent->getHasError()) {
			$event->addContext('consent', 'error get consent information: ' . $consent->getError()->getMessage());
		} else {
			$event->addContext('consent_id', $consent->getId());
			$event->addContext('consent_version', $consent->getVersion());
		}
		$this->auditor->push($event);

		$params = [
			'campaignId' => $requestParams['id'],
			'type' => $requestParams['type'],
			'date_from' => (isset($requestDateParams['campaignReportFilter']['date_from']) && $requestDateParams['campaignReportFilter']['date_from'] !== '')
				? new \DateTime(date('Y-m-d 00:00:00', strtotime(str_replace('/', '-', $requestDateParams['campaignReportFilter']['date_from']))))
				: new \DateTime("NOW -30 days"),
			'date_to' => (isset($requestDateParams['campaignReportFilter']['date_to']) && $requestDateParams['campaignReportFilter']['date_to'] !== '')
				? new \DateTime(date('Y-m-d 23:59:59', strtotime(str_replace('/', '-', $requestDateParams['campaignReportFilter']['date_to']))))
				: new \DateTime("NOW")
		];

		$reportResponse = $this
			->reportService
			->processReport(ReportType::CALL_TO_ACTION, $params, $client);

		$url = $this->controllerHelper->generateUrl('admin_campaign_cta_report_detail', ['id' => $params['campaignId']]);

		if ($reportResponse == 'batch') {
			$this->session->getFlashBag()->add('export', true);
			return new RedirectResponse($url);
		} elseif ($reportResponse == 'empty') {
			$this->session->getFlashBag()->add(
				'notice',
				'Não existem informações à serem exportadas no período selecionado.'
			);
			return new RedirectResponse($url);
		}
        $this->auditLogService->createAuditLog(
            'export-call-to-action',
            Events::create()->getValue(),
            null,
            true
        );

		return $reportResponse;
	}

	public function sms(Request $request)
	{
		$client = $this->getLoggedClient();
		$requestParams = $request->query->all();
		$params = [];
		$filterParams = [];
		$charset = $requestParams['charset'];
		$fileFormat = $requestParams['fileFormat'];

		if (array_key_exists('filters', $requestParams)) {
			parse_str($requestParams['filters'], $filterParams);
		}

		$filterParams['smsReportsFilter']['date_from'] = [];

		if ($filterParams && array_key_exists('smsReportsFilter', $filterParams)) {
			if (reset($filterParams) != '' && array_key_exists('filter', reset($filterParams))) {
				$params['filter'] = reset($filterParams)['filter'];
			}

			if (reset($filterParams) != '' && array_key_exists('date_from', reset($filterParams))) {
				$filterParams['smsReportsFilter']['date_from'] = reset($filterParams)['date_from'];
			}

			foreach ($filterParams['smsReportsFilter'] as $key => $value) {
				$params[$key] = $value;
			}
		}

		$reportResponse = $this
			->reportService
			->processReport(ReportType::SMS, $params, $client, $fileFormat, $charset);

		$url = $this->controllerHelper->generateUrl('admin_sms_report');

		if ($reportResponse == 'batch') {
			$this->session->getFlashBag()->add('export', true);
			$urlParams = ReportService::generateUrlParams($params, "smsReportsFilter");
			$url = $this->controllerHelper->generateUrl('admin_sms_report', $urlParams);
			return new RedirectResponse(urldecode($url));
		} elseif ($reportResponse == 'empty') {
			$this->session->getFlashBag()->add(
				'notice',
				'Não existem informações à serem exportadas no período selecionado.'
			);
			return new RedirectResponse($url);
		}

        $this->auditLogService->createAuditLog(
            'export-sms-report',
            Events::create()->getValue(),
            null,
            true
        );

		return $reportResponse;
	}

	/**
	 * @param Request $request
	 * @return string|RedirectResponse|Response
	 * @throws AuditException
	 */
	public function guestsReport(Request $request)
	{
		$client = $this->getLoggedClient();
		$user = $this->controllerHelper->getUser();
		$traceHeaders = TracerHeaders::from($request);
		$consent = $this->getConsentGateway->get($client, 'pt_BR', $traceHeaders);

		// Audit
		$event = $this
			->auditor
			->newEvent()
			->withClient($client->getId())
			->withSource(Kinds::userAdmin(), $user->getId())
			->withType(Events::accept())
			->onTarget(Kinds::guestsReport(), 'guests_report')
			->addDescription(AuditEvent::PT_BR, 'Usuário exportou relatório de visitantes e aceitou usar os dados de acordo com os termos de consentimento')
			->addDescription(AuditEvent::EN_US, 'User exported visitor report and agreed to use the data in accordance with the consent terms')
			->addDescription(AuditEvent::ES_ES, 'El usuario exportó el informe de visitantes y acordó usar los datos de acuerdo con los términos de consentimiento.');
		if ($consent->getHasError()) {
			$event->addContext('consent', 'Fail to get consent information: ' . $consent->getError()->getMessage());
		} else {
			$event->addContext('consent_id', $consent->getId());
			$event->addContext('consent_version', $consent->getVersion());
		}
		$this->auditor->push($event);

		$params = $request->query->all();
		$charset = $params['charset'];
		$fileFormat = $params['fileFormat'];

		$dateFromObj = new \DateTime("NOW -30 days");
		$dateFrom = isset($params['date_from']) ? $params['date_from'] : $dateFromObj->format('d/m/Y');

		$dateToObj = new \DateTime("NOW");
		$dateTo = isset($params['date_to']) ? $params['date_to'] : $dateToObj->format('d/m/Y');

		if (!isset($params['recurrence']) && empty($params['recurrence'])) {
			if (strpos($params['filters'], 'unique_guests') !== false) {
				$recurrence = 'unique';
			} elseif (strpos($params['filters'], 'returning_guests') !== false) {
				$recurrence = 'recurring';
			}
			else {
				$recurrence = null;
			}
		} else {
			$recurrence = $params['recurrence'];
		}

		$filter = [
			'recurrence' => $recurrence,
			'filter' => (isset($params['range_by']) && !empty($params['range_by'])) ? $params['range_by'] : 'lastAccess',
			'dateFrom' => $dateFrom,
			'dateTo' => $dateTo,
			'charset' => $charset,
			'fileFormat' => $fileFormat
		];

		$reportResponse = $this
			->reportService
			->processReport(ReportType::GUESTS, $filter, $client, $fileFormat, $charset);

		$url = $this->controllerHelper->generateUrl('admin_guests_reports');

		if ($reportResponse == 'batch') {
			$urlParams = ReportService::generateUrlParams($params, "guestReportsFilter");
			$url = $this->controllerHelper->generateUrl('admin_guests_reports', $urlParams);
			$this->session->getFlashBag()->add('export', true);
			return new RedirectResponse($url);
		} elseif ($reportResponse == 'empty') {
			$this->session->getFlashBag()->add(
				'notice',
				'Não existem informações à serem exportadas no período selecionado.'
			);
			return new RedirectResponse($url);
		}

        $this->auditLogService->createAuditLog(
            'export-guests-report',
            Events::create()->getValue(),
            null,
            true
        );

		return $reportResponse;
	}

	public function birthdays(Request $request)
	{
		$client = $this->getLoggedClient();
		$requestParams = $request->query->all();
		$params = [];
		$filterParams = [];
		$charset = $requestParams['charset'];
		$fileFormat = $requestParams['fileFormat'];

		if (array_key_exists('filters', $requestParams)) {
			parse_str($requestParams['filters'], $filterParams);
		}

		$filterParams['monthFilter']['month'] = [];

		if ($filterParams && array_key_exists('monthFilter', $filterParams)) {
			if (reset($filterParams) != '' && array_key_exists('month', reset($filterParams))) {
				$filterParams['monthFilter']['month'] = reset($filterParams)['month'];
			}

			foreach ($filterParams['monthFilter'] as $key => $value) {
				$params[$key] = $value;
			}
		}

		$reportResponse = $this
			->reportService
			->processReport(ReportType::BIRTHDAYS, $params, $client, $fileFormat, $charset);

		$url = $this->controllerHelper->generateUrl('admin_birthdays_reports');

		if ($reportResponse == 'batch') {
			$urlParams = ReportService::generateUrlParams($params, "monthFilter");
			$url = $this->controllerHelper->generateUrl('admin_birthdays_reports', $urlParams);
			$this->session->getFlashBag()->add('export', true);
			return new RedirectResponse($url);
		} elseif ($reportResponse == 'empty') {
			$this->session->getFlashBag()->add(
				'notice',
				'Não existem informações à serem exportadas no período selecionado.'
			);
			return new RedirectResponse($url);
		}

		return $reportResponse;
	}
}
