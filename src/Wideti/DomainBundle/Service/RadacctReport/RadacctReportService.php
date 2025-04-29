<?php

namespace Wideti\DomainBundle\Service\RadacctReport;

use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\ReportHelper;
use Wideti\DomainBundle\Helpers\WifiMode;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\RadacctRepositoryAware;
use Wideti\DomainBundle\Repository\Elasticsearch\Report\ReportRepositoryAware;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointsService;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\RadacctReport\Dto\GuestAccessReport;
use Wideti\DomainBundle\Service\RadacctReport\Dto\GuestUniqueAndRecurringDashboard;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\DomainBundle\Repository\AccessPointsRepository;

class RadacctReportService
{
    use RadacctRepositoryAware;
    use ReportRepositoryAware;
    use MongoAware;

    /**
     * @var WifiMode
     */
    private $wifiMode;
    /**
     * @var AccessPointsService
     */
    private $accessPointsService;
    /**
     * @var CacheServiceImp
     */
    private $cacheService;
    /**
     * @var AccessPointsRepository
     */
	private $accessPointsRepository;

    public function __construct(
        WifiMode $wifiMode,
        AccessPointsService $accessPointsService,
        AccessPointsRepository $accessPointsRepository,
        CacheServiceImp $cacheService
    ) {
        $this->wifiMode = $wifiMode;
        $this->accessPointsService = $accessPointsService;
        $this->accessPointsRepository = $accessPointsRepository;
        $this->cacheService = $cacheService;
    }

    public function findAccountingByFilter($filters, $period, $page, $offset = 10, $order = "desc")
    {
        $accountingsQuery = $this->radacctRepository->findFiltered($filters, $period, $page, $offset, $order);

        $accountings = [];
        if ($accountingsQuery["hits"]["total"] > 0) {
            foreach ($accountingsQuery["hits"]["hits"] as $row) {
                $newRow       = $row["_source"];
                $newRow["id"] = $row["_id"];

                $accountings[] = $newRow;

                unset($newRow);
            }
        }

        return $accountings;
    }

    public function countByQuery($filters, $period)
    {
        return $this->radacctRepository->countByQuery($filters, $period);
    }

    public function getDownloadUploadByDate(
        Client $client,
        $period,
        $access_point = null,
        $interval,
        $formatRange,
        $formatAggregation
    ) {
        $downloadField  = 'download';
        $uploadField    = 'upload';

        return $this->reportRepository->getDownloadUploadByDate(
            $client,
            $period,
            $access_point,
            $downloadField,
            $uploadField,
            $interval,
            $formatRange,
            $formatAggregation
        );
    }

    public function getVisitsAndRecordsPerAccessPoint(
        Client $client,
        $filterRangeDate = null,
        $accessPoints = [],
        $numberOfAps = 5
    ) {
        if (!$filterRangeDate) {
            $filterRangeDate = [
                'date_from' => date_format(new \DateTime("NOW -7 days"), 'Y-m-d'),
                'date_to'   => date_format(new \DateTime("NOW"), 'Y-m-d')
            ];
        }

        return $this->processVisitsAndRecordsPerAp($client, $filterRangeDate, $numberOfAps, $accessPoints);
    }

    public function getTotalVisitsByUsername($username, $period, $filterRange = null)
    {
        return $this->radacctRepository->getTotalVisitsByUsername($username, $period, $filterRange);
    }

    /**
     * @param Client $client
     * @param $params
     * @return int
     */
    public function countAllVisits(Client $client, $params)
    {
        $dateFormat = "Y-m-d H:i:s";

        $from = \DateTime::createFromFormat($dateFormat, $params['filters']['dateFrom']);
        $to = \DateTime::createFromFormat($dateFormat, $params['filters']['dateTo']);

        $totalVisits = $this->radacctRepository->countAllVisitsPerClient($client, $from, $to);

        return $totalVisits;
    }

    public function getAccountingByGuests(
        Client $client,
        $period,
        array $usersMongo,
        array $orderField = null,
        $filterRange = null
    ) {
        $downloadField  = 'download';
        $uploadField    = 'upload';

        $accountingsQuery = $this->radacctRepository
            ->getAccountingByGuests(
                $client,
                $period,
                $usersMongo,
                $downloadField,
                $uploadField,
                $orderField,
                $filterRange
            );

        $accountings = [];

        if (count($accountingsQuery['aggregations']['guest']['buckets']) > 0) {
            foreach ($accountingsQuery['aggregations']['guest']['buckets'] as $row) {
                $newRow = $row;

                $newRow['total_visits'] = $this->getTotalVisitsByUsername($newRow['key'], $period, $filterRange);

                if ($newRow['doc_count'] > 0) {
                    $accountings[] = $newRow;
                }
                unset($newRow);
            }
        }
        return $accountings;
    }

    public function processVisitsAndRecordsPerDay(Client $client, $filterDate, $accessPoints = [])
    {
        $visits = $this->reportRepository->getAllVisitsAndRegistersPerDay($client, $filterDate, $accessPoints);
        return ($visits['hits']['total'] > 0) ? $visits['aggregations']['visits_records_per_day']['buckets'] : [];
    }

    public function processAllVisitsAndRecordsPerDay(Client $client, $filterDate, $accessPoints = [])
    {
        $visits = $this->reportRepository->mostAllAccessedHoursByClient($client, $filterDate, $accessPoints);
        return $visits;
    }

    /**
     * @param Client $client
     * @param $filterRangeDate
     * @param int $numberOfAps
     * @param AccessPoints[] $accessPoints
     * @return array
     */
    private function processVisitsAndRecordsPerAp(
        Client $client,
        $filterRangeDate,
        $numberOfAps = 5,
        $accessPoints = []
    ) {
        return $this->reportRepository->getVisitsAndRecordsPerAccessPoint(
            $client,
            $filterRangeDate,
            $accessPoints,
            $numberOfAps
        );
    }

    public function mostAccessesHours(Client $client, $filterRangeDate, $accessPoints = [], $prepareToGraph = false)
    {
        $result = $this->reportRepository->mostAccessedHoursByClient(
            $client,
            $filterRangeDate,
            $accessPoints
        );

        return ($prepareToGraph) ? ReportHelper::mostAccessesHours($result) : $result;
    }

    public function dashBoardDownloadUpload(Client $client, $filterRangeDate = null)
    {
        $downloadField  = 'download';
        $uploadField    = 'upload';

        $period = [
            'from'  => $filterRangeDate['date_from'],
            'to'    => $filterRangeDate['date_to']
        ];

        return $this->reportRepository->getDownloadUploadByDate(
            $client,
            $period,
            null,
            $downloadField,
            $uploadField,
            "day",
            "yyyy-MM-dd HH:mm:ss"
        );
    }

    public function averageConnectionTimeByClient(Client $client, $filterRangeDate = null)
    {
        $averageConnectionTime = $this->radacctRepository->averageConnectionTimeByClient($client, $filterRangeDate);

        if ($averageConnectionTime['hits']['total'] == 0) return;

        $totalSeconds   = (int)$averageConnectionTime['aggregations']['total_access_time_in_seconds']['value'];
        $averageSeconds = (int)($averageConnectionTime['hits']['total'] == 0) ? 1 : $averageConnectionTime['hits']['total'];
        $average        = (int)abs(intval(substr($totalSeconds, 0, -3) / $averageSeconds));

        return $average;
    }

    public function mostDataTrafficApsByClient(Client $client, $filterRangeDate = null)
    {
        $data = $this->reportRepository->mostDataTrafficApsByClient($client, $filterRangeDate);
        return ($data['hits']['total'] > 0) ? $data['aggregations']['aps_most_data_traffic']['buckets'] : [];
    }

    public function getTotalAccessByAp(Client $client) {
        $accountings  = $this->radacctRepository->getAccoutingsByAp($client);
        $access_points = $this->accessPointsRepository->getAccessPointsList(
            $client->getId()
        );
        $access_points_by_identifier = [];

        foreach($access_points as $ap) {
            $apDetails = [
                'location' => $ap['location'],
                'friendly_name' => $ap['friendly_name']
            ];
            $access_points_by_identifier[$ap["identifier"]] = $apDetails;
        }

        $onlineGuests = [];

        foreach ($accountings as $apAccessCount) {
            $access_point = $access_points_by_identifier[$apAccessCount['key']];
            if ($access_point) {
                $data = [];
                
                $data['count'] = $apAccessCount['doc_count'];
                $data['locale'] = $access_point['location'];
                $data['identifier'] = $apAccessCount['key'];
                $data['friendly_name'] = $access_point['friendly_name'];

                array_push($onlineGuests, $data);
            }
        }

        return $onlineGuests;
    }

    public function getOnlineGuests(Client $client, $filter = [])
    {
        $accountings  = $this->radacctRepository->getOnlineAccountings($client, $filter);
        $access_points = $this->accessPointsRepository->getAccessPointsList(
            $client->getId()
        );
        $access_points_by_identifier = [];

        foreach($access_points as $ap) {
            $apDetails = [
                'public_ip' => $ap['public_ip']
            ];
            $access_points_by_identifier[$ap["identifier"]] = $apDetails;
        }

        $onlineGuests = [];

        foreach ($accountings as $acct) {
            $guest = $this->mongo
                ->getRepository('DomainBundle:Guest\Guest')
                ->findOneBy([
                    'mysql' => $acct['username']
                ]);

            if ($guest) {
                $data = [];
                $data['guest_' . $guest->getLoginField()] = $guest->getProperties()[$guest->getLoginField()];
                $data['guest_id'] = $guest->getId();
                $data['guest_name'] = isset($guest->getProperties()['name']) ? $guest->getProperties()['name'] : '';
                $data['guest_properties'] = $guest->getProperties();
                $data['acctstarttime'] = $acct['acctstarttime'];
                $data['framedipaddress'] = $acct['framedipaddress'];
                $data['calledstation_name'] = $acct['calledstation_name'];
                $data['timezone'] = $acct['timezone'];
                $data['guest_mysql'] = $guest->getMysql();
                $data['guest_elasticsearch_username'] = $acct['username'];
                $data['guest_sessionid'] = $acct['acctsessionid'];
                $access_point = $access_points_by_identifier[$acct['calledstationid']];
                $data['ap_public_ip'] = $access_point["public_ip"] ? $access_point["public_ip"] : "";

                array_push($onlineGuests, $data);
            }
        }

        return $onlineGuests;
    }

    /**
     * @param Client $client
     * @param array $filter
     * @return int
     */
    public function totalOnlineGuests(Client $client, $filter = [])
    {
        return $this->radacctRepository->totalOnlineAccountings($client, $filter);
    }


    /**
     * @param Client $client
     * @param $fieldToFilter
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param $recurrence
     * @return array
     */
    public function retrieveGuestsIds(
        Client $client,
        $fieldToFilter,
        \DateTime $dateFrom,
        \DateTime $dateTo
    ) {
        $guestsIds = $this->radacctRepository->retrieveGuestsIds(
            $client,
            $fieldToFilter,
            $dateFrom,
            $dateTo
        );

        if ($guestsIds['hits']['total'] > 0) {
            return $guestsIds['aggregations']['guest']['buckets'];
        }
        return [];
    }

    /**
     * @param Client $client
     * @param $fieldToFilter
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param $recurrence
     * @return array
     */
    public function getGuestAccessReport(
        Client $client,
        $fieldToFilter,
        \DateTime $dateFrom,
        \DateTime $dateTo,
        $recurrence,
        $guestsIds
    ) {
        $result = $this->radacctRepository->groupByGuestWithDownloadUploadAverageTimeTotalVisits(
            $client,
            $fieldToFilter,
            $dateFrom,
            $dateTo,
            $guestsIds,
            'download',
            'upload'

        );

        if ($result['hits']['total'] > 0) {
            $data = [];
	        foreach ($result['aggregations']['guest']['buckets'] as $accessData) {
                $totalVisits = $accessData['doc_count'];

                if ($recurrence == 'unique' && $totalVisits > 1) continue;
                if ($recurrence == 'recurring' && $totalVisits <= 1) continue;

                $guestInfo = $accessData['guest_docs']['hits']['hits'][0]['_source'];
                $guestReport = new GuestAccessReport();
                $guestReport->setUserNameId($accessData['key']);
                $guestReport->setGuestName($guestInfo['name']);
                $guestReport->setLoginFieldValue($guestInfo['loginValue']);
                $guestReport->setDownloadTotal($accessData['download']['value']);
                $guestReport->setUploadTotal($accessData['upload']['value']);
                $guestReport->setTotalOfVisits($accessData['doc_count']);
                $guestReport->setAverageTime($accessData['averageTime']['value']);
                $guestReport->setLastAccessDate(date('d/m/Y H:i:s', strtotime($guestInfo['lastAccess'])));
                $guestReport->setRegisterDate(date('d/m/Y H:i:s', strtotime($guestInfo['created'])));
                array_push($data, $guestReport);
	        }
	        return $data;
        }

        return [];
    }

	/**
	 * @param Client $client
	 * @param \DateTime $dateFrom
	 * @param \DateTime $dateTo
	 * @return \Generator
	 */
	public function getAggregateGuestByRange(Client $client, \DateTime $dateFrom, \DateTime $dateTo)
	{
		$result = $this->radacctRepository->groupByGuest($client, $dateFrom, $dateTo);

		if ($result['hits']['total'] == 0) return;

		foreach ($result['aggregations']['guest']['buckets'] as $accessData) {
			$guestReport = new GuestUniqueAndRecurringDashboard();
			$guestReport->setUserNameId($accessData['key']);
			yield $guestReport;
		}
	}
}
