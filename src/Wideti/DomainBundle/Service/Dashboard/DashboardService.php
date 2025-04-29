<?php

namespace Wideti\DomainBundle\Service\Dashboard;

use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\DateTimeHelper;
use Wideti\DomainBundle\Helpers\ReportHelper;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Guest\GuestService;
use Wideti\DomainBundle\Service\GuestDevices\GuestDevices;
use Wideti\DomainBundle\Service\OnlineGuests\GetTotalOnlineGuestsByClient;
use Wideti\DomainBundle\Service\RadacctReport\RadacctReportServiceAware;
use Wideti\DomainBundle\Service\ReportBuilder\ReportBuilderServiceAware;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\DomainBundle\Helpers\ConvertSecondToTime;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class DashboardService
{
    use EntityManagerAware;
    use MongoAware;
    use ReportBuilderServiceAware;
    use RadacctReportServiceAware;
    use SessionAware;

    /**
     * @var GuestService
     */
    private $guestService;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
	/**
	 * @var CacheServiceImp
	 */
	private $cacheService;
    /**
     * @var GetTotalOnlineGuestsByClient
     */
    private $getTotalOnlineGuestsByClient;
    /**
     * @var GuestDevices
     */
    private $guestDevices;

    /**
     * DashboardService constructor.
     * @param ConfigurationService $configurationService
     * @param GuestService $guestService
     * @param CacheServiceImp $cacheService
     * @param GetTotalOnlineGuestsByClient $getTotalOnlineGuestsByClient
     * @param GuestDevices $guestDevices
     */
	public function __construct(
        ConfigurationService $configurationService,
        GuestService $guestService,
		CacheServiceImp $cacheService,
        GetTotalOnlineGuestsByClient $getTotalOnlineGuestsByClient,
        GuestDevices $guestDevices
    ) {
        $this->configurationService = $configurationService;
        $this->guestService         = $guestService;
		$this->cacheService         = $cacheService;
        $this->getTotalOnlineGuestsByClient = $getTotalOnlineGuestsByClient;
        $this->guestDevices = $guestDevices;
    }

    public function home($client, $filter = null)
    {
        $filterDate = $this->generateRangeDate($filter);

        $visitsAndRegistersPerDay = ReportHelper::allAccessAndRegister(
            $this->getAlltVisitsAndRegistersPerDay($client, $filterDate)
        );

        $countAPs = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->count($client, [
                'status' => AccessPoints::ACTIVE
            ]);

        $dashboardPiechart = $this->getMostAccessedAPChart($client, $filterDate);
        $chartDataAcessByHour = $this->getAccessByHourChart($client, $filterDate);

        return [
            'total_aps'         => $countAPs,
            'guestChart'        => $visitsAndRegistersPerDay,
            'dashboardPiechart' => $dashboardPiechart,
            'accessByHour'      => $chartDataAcessByHour
        ];
    }

    public function overview($client)
    {
        $countOnlineGuestsAPI = $this->getTotalOnlineGuestsByClient->get($client);
        $countOnlineGuests  = $this->radacctReportService->totalOnlineGuests($client, []);
        $filterDate         = $this->getRangeDate();

        $params = [
            'filters' => [
                'dateFrom'  => $filterDate['date_from'],
                'dateTo'    => $filterDate['date_to']
            ]
        ];

        $countTotalGuests = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->countByFilter($params);

        $countAPs = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->count($client, [
                'status' => AccessPoints::ACTIVE
            ]);

        $countVisits = $this->radacctReportService->countAllVisits($client, $params);

        return [
            'countOnlineGuests'     => $countOnlineGuests,
            'countOnlineGuestsAPI'  => $countOnlineGuestsAPI,
            'countTotalGuests'      => $countTotalGuests,
            'countAps'              => $countAPs,
            'countVisits'           => $countVisits
        ];
    }

    /**
     * @param $client
     * @return array
     * @throws \Exception
     */
    public function guests($client)
    {
        $client     = $this->getLoggedClient();
        $nas        = $this->session->get(Nas::NAS_SESSION_KEY);
        $filterDate = $this->getRangeDate();

        $params = [
            'filters' => [
                'dateFrom'  => $filterDate['date_from'],
                'dateTo'    => $filterDate['date_to']
            ]
        ];

        $countTotalGuests = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->countByFilter($params);

        $countOfUniqueAndRecurringGuests = $this->guestService
            ->getAmountOfUniqueAndReturningGuestsLastMonth($client);

        $uniqueAccessGuests     = $countOfUniqueAndRecurringGuests['unique'];
        $returningAccessGuests  = $countOfUniqueAndRecurringGuests['recurring'];

        $countOnlineGuests      = $this->radacctReportService->totalOnlineGuests($client, []);
        $totalVisits            = $uniqueAccessGuests + $returningAccessGuests;

        $percentageNewGuests       = ($totalVisits > 0) ? round((($uniqueAccessGuests) / $totalVisits) * 100) : 0;
        $percentageReturningGuests =
            ($totalVisits > 0) ? round((($returningAccessGuests) / $totalVisits) * 100) : 0;

        $accessData = $this->getAccessData($client, $filterDate);

        if ($this->configurationService->get($nas, $client, 'facebook_login') == 1 or
            $this->configurationService->get($nas, $client, 'twitter_login') == 1
        ) {
            $accessData['registerMode'] = $this->getSignUpOrigin($filterDate);
        } else {
            $visitsAndRegistersPerDay = ReportHelper::allAccessAndRegister(
                $this->getAlltVisitsAndRegistersPerDay($client, $filterDate)
            );

            $accessData['registerMode'] = $visitsAndRegistersPerDay;
        }

        $countAPs = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->count($client, [
                'status' => AccessPoints::ACTIVE
            ]);


        $dashboardPiechart = $this->getMostAccessedAPChart($client, $filterDate);
        $chartDataAcessByHour = $this->getAccessByHourChart($client, $filterDate);

        return [
            'uniqueAccessGuests'        => $uniqueAccessGuests,
            'returningAccessGuests'     => $returningAccessGuests,
            'countTotalGuests'          => $countTotalGuests,
            'countOnlineGuests'         => $countOnlineGuests,
            'percentageNewGuests'       => $percentageNewGuests,
            'percentageReturningGuests' => $percentageReturningGuests,
            'accessData'                => $accessData,
            'total_aps'                 => $countAPs,
            'dashboardPiechart'         => $dashboardPiechart,
            'accessByHour'              => $chartDataAcessByHour
        ];
    }

    /**
     * @param $client
     * @return array
     * @throws \Exception
     */
    public function network($client)
    {
        $filterDate = $this->getRangeDate();

        $downloadUpload = $this->getDownloadUpload($client, $filterDate);

        $downloadUploadArray = ($downloadUpload['hits']['total'] > 0)
	        ? $downloadUpload['aggregations']['download_upload']['buckets']
            : [];

        $downloadLastDay    = 0;
        $uploadLastDay      = 0;

        $downloadTotal      = 0;
        $uploadTotal        = 0;

        $previousDay        = date('Y-m-d', strtotime("-1 day", strtotime(date('Y-m-d'))));

        foreach ($downloadUploadArray as $downUpArray) {
            if ($previousDay == $downUpArray['key_as_string']) {
                $downloadLastDay = $downUpArray['download']['value'];
                $uploadLastDay   = $downUpArray['upload']['value'];
            }

            $downloadTotal += $downUpArray['download']['value'];
            $uploadTotal   += $downUpArray['upload']['value'];
        }

        $seconds = $this->getAverageConnectionTime($client, $filterDate);

        $results    = [];
        $chartData  = [
            'signIns' => []
        ];

        $qtdeAps = 1;

        $mostDataTrafficSearch = $this->getMostTrafficAps($client, $filterDate);

        foreach ($mostDataTrafficSearch as $data) {
            $accessPoint = $this->em
                ->getRepository("DomainBundle:AccessPoints")
                ->findOneBy([
                    'friendlyName' => $data['key'],
                    'client'       => $client,
                    'status'       => AccessPoints::ACTIVE
                ]);

            $accessPointName = $data['key'];

            if ($accessPoint) {
                $accessPointName = $accessPoint->getFriendlyName();
            }

            if ($accessPoint && $accessPoint->getStatus() == AccessPoints::ACTIVE && $qtdeAps <= 5) {
                array_push(
                    $results,
                    [
                        'label' => $accessPointName,
                        'data'  => $data['traffic']['value']
                    ]
                );
                $qtdeAps++;
            }
        }

        $apsArray = [];

        foreach ($results as $result) {
            $apsArray[$result['label']][] = $result['data'];
        }

        foreach ($apsArray as $labels => $data) {
            $chartData['signIns'][] = [
                'label' => $labels,
                'data'  => array_sum($data)
            ];
        }

        $dashboardPiechart = ($chartData);

        return [
            'downloadLastDay'       => $downloadLastDay,
            'uploadLastDay'         => $uploadLastDay,
            'downloadTotal'         => $downloadTotal,
            'uploadTotal'           => $uploadTotal,
            'averageConnectionTime' => ConvertSecondToTime::convert($seconds),
            'chartAccess'           => [
                'pieChart' => $dashboardPiechart
            ]
        ];
    }

    private function generateRangeDate($filter)
    {
        if ($filter['filter'] == '') {
            $dateFrom = new \DateTime(date('Y-m-d', strtotime('2014-01-01 00:00:00')));
            $dateTo   = new \DateTime('NOW');
        } elseif ($filter['filter'] == 'last30days') {
            $dateFrom = new \DateTime('-30 days');
            $dateTo   = new \DateTime('NOW');
        } elseif ($filter['filter'] == 'custom') {
            $dateFrom = new \DateTime(date('Y-m-d', strtotime(str_replace('/', '-', $filter['date_from']))));
            $dateTo   = new \DateTime(date('Y-m-d', strtotime(str_replace('/', '-', $filter['date_to']))));
        }

	    $this->session->set('dashboardFilter', (bool) $filter);
	    $this->session->set('dashboardFilterOption', $filter['filter']);
	    $this->session->set('dashboardFilterDateFrom', date_format($dateFrom, 'Y-m-d 00:00:00'));
        $this->session->set('dashboardFilterDateTo', date_format($dateTo, 'Y-m-d 23:59:59'));

        return $this->getRangeDate();
    }

    private function getRangeDate()
    {
        return [
            'date_from' => $this->session->get('dashboardFilterDateFrom'),
            'date_to'   => $this->session->get('dashboardFilterDateTo'),
            'filtered'  => $this->session->get('dashboardFilter')
        ];
    }

	/**
	 * @param $client
	 * @param $filterDate
	 * @return array|mixed
	 * @throws \Exception
	 */
    protected function getVisitsAndRegistersPerDay($client, $filterDate)
    {
        $filterDate = [
            'date_from' => (new \DateTime('-7 days'))->format('Y-m-d 00:00:00'),
            'date_to'   => (new \DateTime('-1 day'))->format('Y-m-d 23:59:59'),
            'filtered'  => true
        ];

        if (!$this->cacheService->isActive()) {
            $data = $this->radacctReportService->processVisitsAndRecordsPerDay($client, $filterDate);
        } else {
            if ($this->cacheService->exists(CacheServiceImp::DASHBOARD_VISITS_REGISTERS_PER_DAY) !== 1 || $filterDate['filtered']) {
                $result = $this->radacctReportService->processVisitsAndRecordsPerDay($client, $filterDate);

                $this->cacheService->set(
                    CacheServiceImp::DASHBOARD_VISITS_REGISTERS_PER_DAY,
                    $result,
                    DateTimeHelper::timeToLiveCache()
                );
            }

            $data = $this->cacheService->get(CacheServiceImp::DASHBOARD_VISITS_REGISTERS_PER_DAY);
        }

        return $data;
    }

    /**
     * @param $client
     * @param $filterDate
     * @return array|mixed
     * @throws \Exception
     */
    protected function getAlltVisitsAndRegistersPerDay($client, $filterDate)
    {
        $filterDate = [
            'date_from' => (new \DateTime('-7 days'))->format('Y-m-d 00:00:00'),
            'date_to'   => (new \DateTime('-1 day'))->format('Y-m-d 23:59:59'),
            'filtered'  => true
        ];

        if (!$this->cacheService->isActive()) {
            $data = $this->radacctReportService->processAllVisitsAndRecordsPerDay($client, $filterDate);
        } else {
            if ($this->cacheService->exists(CacheServiceImp::DASHBOARD_VISITS_REGISTERS_PER_DAY) !== 1 || $filterDate['filtered']) {
                $result = $this->radacctReportService->processAllVisitsAndRecordsPerDay($client, $filterDate);

                $this->cacheService->set(
                    CacheServiceImp::DASHBOARD_VISITS_REGISTERS_PER_DAY,
                    $result,
                    DateTimeHelper::timeToLiveCache()
                );
            }

            $data = $this->cacheService->get(CacheServiceImp::DASHBOARD_VISITS_REGISTERS_PER_DAY);
        }

        return $data;
    }

    /**
     * @param $filterDate
     * @return mixed
     */
    protected function getSignUpOrigin($filterDate)
    {
        if (!$this->cacheService->isActive()) {
            $data = $this->mongo
                ->getRepository('DomainBundle:Guest\Guest')
                ->graphRegisterMode($filterDate);
        } else {
            if ($this->cacheService->exists(CacheServiceImp::DASHBOARD_SIGNUPS_ORIGIN) !== 1 || $filterDate['filtered']) {
                $result = $this->mongo
                    ->getRepository('DomainBundle:Guest\Guest')
                    ->graphRegisterMode($filterDate);

                $this->cacheService->set(
                    CacheServiceImp::DASHBOARD_SIGNUPS_ORIGIN,
                    $result,
                    DateTimeHelper::timeToLiveCache()
                );
            }

            $data = $this->cacheService->get(CacheServiceImp::DASHBOARD_SIGNUPS_ORIGIN);
        }

        return $data;
    }

    /**
     * @param Client $client
     * @param $filterDate
     * @return mixed
     */
    protected function getAccessData(Client $client, $filterDate)
    {
        if (!$this->cacheService->isActive()) {
            $data = $this->guestDevices->graphAccessData($client, $filterDate);
        } else {
            if ($this->cacheService->exists(CacheServiceImp::DASHBOARD_ACCESS_DATA) !== 1 || $filterDate['filtered']) {
                $result = $this->guestDevices->graphAccessData($client, $filterDate);

                $this->cacheService->set(
                    CacheServiceImp::DASHBOARD_ACCESS_DATA,
                    $result,
                    DateTimeHelper::timeToLiveCache()
                );
            }

            $data = $this->cacheService->get(CacheServiceImp::DASHBOARD_ACCESS_DATA);
        }

        return $data;
    }

    /**
     * @param $client
     * @param $filterDate
     * @return number
     */
    protected function getAverageConnectionTime($client, $filterDate)
    {
        if (!$this->cacheService->isActive()) {
            $data = $this->radacctReportService->averageConnectionTimeByClient($client, $filterDate);
        } else {
            if ($this->cacheService->exists(CacheServiceImp::DASHBOARD_AVERAGE_CONNECTION_TIME) !== 1 || $filterDate['filtered']) {
                $result = $this->radacctReportService->averageConnectionTimeByClient($client, $filterDate);

                $this->cacheService->set(
                    CacheServiceImp::DASHBOARD_AVERAGE_CONNECTION_TIME,
                    $result,
                    DateTimeHelper::timeToLiveCache()
                );
            }

            $data = $this->cacheService->get(CacheServiceImp::DASHBOARD_AVERAGE_CONNECTION_TIME);
        }

        return $data;
    }

    /**
     * @param $client
     * @param $filterDate
     * @return mixed
     */
    protected function getMostTrafficAps($client, $filterDate)
    {
        if (!$this->cacheService->isActive()) {
            $data = $this->radacctReportService->mostDataTrafficApsByClient($client, $filterDate);
        } else {
            if ($this->cacheService->exists(CacheServiceImp::DASHBOARD_MOST_TRAFFIC_APS) !== 1 || $filterDate['filtered']) {
                $result = $this->radacctReportService->mostDataTrafficApsByClient($client, $filterDate);

                $this->cacheService->set(
                    CacheServiceImp::DASHBOARD_MOST_TRAFFIC_APS,
                    $result,
                    DateTimeHelper::timeToLiveCache()
                );
            }

            $data = $this->cacheService->get(CacheServiceImp::DASHBOARD_MOST_TRAFFIC_APS);
        }

        return $data;
    }

    /**
     * @param $client
     * @param $filterDate
     * @return array
     */
    protected function getDownloadUpload($client, $filterDate)
    {
        if (!$this->cacheService->isActive()) {
            $data = $this->radacctReportService->dashBoardDownloadUpload($client, $filterDate);
        } else {
            if ($this->cacheService->exists(CacheServiceImp::DASHBOARD_DOWNLOAD_UPLOAD) !== 1 || $filterDate['filtered']) {
                $result = $this->radacctReportService->dashBoardDownloadUpload($client, $filterDate);

                $this->cacheService->set(
                    CacheServiceImp::DASHBOARD_DOWNLOAD_UPLOAD,
                    $result,
                    DateTimeHelper::timeToLiveCache()
                );
            }

            $data = $this->cacheService->get(CacheServiceImp::DASHBOARD_DOWNLOAD_UPLOAD);
        }

        return $data;
    }

    /**
     * @param $client
     * @param $filterDate
     * @return array
     */
    private function getMostAccessedAPChart($client, $filterDate)
    {
        $results = [];
        $qtdeAps = 1;

        $apsMostAccess = $this->radacctReportService->getVisitsAndRecordsPerAccessPoint(
            $client,
            [
                'date_from' => date('Y-m-d', strtotime($filterDate['date_from'])),
                'date_to' => date('Y-m-d', strtotime($filterDate['date_to']))
            ]
        );

        foreach ($apsMostAccess as $data) {
            $accessPoint = $this->em
                ->getRepository("DomainBundle:AccessPoints")
                ->findOneBy([
                    'friendlyName' => $data['key'],
                    'client' => $client,
                    'status' => AccessPoints::ACTIVE
                ]);

            if ($accessPoint && $accessPoint->getStatus() == AccessPoints::ACTIVE && $qtdeAps <= 5) {
                $results['signIns'][] = [
                    'label' => $data['key'],
                    'data' => $data['totalVisits']['value']
                ];
                $qtdeAps++;
            }
        }

        $dashboardPiechart = $results;
        return $dashboardPiechart;
    }

    /**
     * @param $client
     * @param $filterDate
     * @return array
     */
    private function getAccessByHourChart($client, $filterDate)
    {
        $chartDataAcessByHour = [
            'categories' => [],
            'quantity' => []
        ];

        if (!$this->cacheService->isActive()) {
            $accessByHourSearch = $this->radacctReportService->mostAccessesHours($client, $filterDate, [], true);
        } else {
            if ($this->cacheService->exists(CacheServiceImp::DASHBOARD_MOST_ACCESSED_HOURS) !== 1 || $filterDate['filtered']) {

                $result = $this->radacctReportService->mostAccessesHours($client, $filterDate, [], true, 'visits');

                $this->cacheService->set(
                    CacheServiceImp::DASHBOARD_MOST_ACCESSED_HOURS,
                    $result,
                    DateTimeHelper::timeToLiveCache()
                );
            }

            $accessByHourSearch = $this->cacheService->get(CacheServiceImp::DASHBOARD_MOST_ACCESSED_HOURS);
        }

        if (!empty($accessByHourSearch)) {
	        $chartDataAcessByHour['categories'] = $accessByHourSearch['hour'];
	        $chartDataAcessByHour['quantity'] = $accessByHourSearch['totalVisits'];
        }

        return $chartDataAcessByHour;
    }
}
