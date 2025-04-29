<?php

namespace Wideti\DomainBundle\Service\WSpotFaker;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Document\Repository\GuestRepository;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\FakerHelper;
use Wideti\DomainBundle\Repository\AccessPointsRepository;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\RadacctRepository;
use Wideti\DomainBundle\Repository\Elasticsearch\Report\ReportRepository;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;

class ReportsFaker implements WSpotFaker
{
    /**
     * @var GuestRepository
     */
    private $guestRepository;
    /**
     * @var ElasticSearch
     */
    private $elasticSearch;
    /**
     * @var AccessPointsRepository
     */
    private $accessPointsRepository;
    /**
     * @var RadacctRepository
     */
    private $radacctRepository;
    /**
     * @var ReportRepository
     */
    private $reportRepository;

    /**
     * AccountingFaker constructor.
     * @param GuestRepository $guestRepository
     * @param AccessPointsRepository $accessPointsRepository
     * @param ElasticSearch $elasticSearch
     * @param RadacctRepository $radacctRepository
     * @param ReportRepository $reportRepository
     */
    public function __construct(
        GuestRepository $guestRepository,
        AccessPointsRepository $accessPointsRepository,
        ElasticSearch $elasticSearch,
        RadacctRepository $radacctRepository,
        ReportRepository $reportRepository
    ) {
        $this->guestRepository = $guestRepository;
        $this->accessPointsRepository = $accessPointsRepository;
        $this->elasticSearch = $elasticSearch;
        $this->radacctRepository = $radacctRepository;
        $this->reportRepository = $reportRepository;
    }

    public function create(Client $client = null, $guests = [])
    {
        if (!$client) throw new \InvalidArgumentException('Client cannot be null');

        $count = 1;

        /**
         * @var $guests Guest
         * @var $guest Guest
         */
        foreach ($guests as $guest) {
            /**
             * @var $createdDate \DateTime
             * @var $customDate \DateTime
             */
            if ($guest->getCreated() instanceof  \DateTime) {
                $createdDate = $guest->getCreated();
            } else {
                $createdDate = new \DateTime(date('Y-m-d H:i:s', $guest->getCreated()->sec));
            }

            $accessPoint = $this->accessPointsRepository->findOneBy([
                'client' => $client,
                'identifier' => $guest->getRegistrationMacAddress()
            ]);

            if (!$accessPoint) {
                $accessPoint = $this->accessPointsRepository->findOneBy([
                    'client' => $client,
                    'vendor' => 'mikrotik'
                ]);
            }

            $this->visitsRegistrationsPerHour($client, $accessPoint, $createdDate);
            $this->downloadUpload($client, $accessPoint, $createdDate);
            $this->visitors($client, $createdDate, $guest);

            $count++;
        }

        return true;
    }

    public function clear(Client $client = null)
    {
        if (!$client) throw new \InvalidArgumentException('Client cannot be null');

        $reportAlias = [
            ElasticSearch::REPORT_VISITS_REGISTRATION_PER_HOUR_ALIAS,
            ElasticSearch::REPORT_DOWNLOAD_UPLOAD_ALIAS,
            ElasticSearch::REPORT_GUESTS_ALIAS
        ];

        foreach ($reportAlias as $alias) {
            $indexes = $this->reportRepository->getAllIndexes($client->getId(), $alias);

            foreach ($indexes as $index) {
                do {
                    $elasticSearchObject = [];

                    $search = [
                        "size" => 10000,
                        "query" => [
                            "term" => [
                                "clientId" => $client->getId()
                            ]
                        ]
                    ];

                    $data = $this->elasticSearch->search('report', $search, $index['key']);

                    if ($data['hits']['total'] > 0) {
                        foreach ($data['hits']['hits'] as $accounting) {
                            array_push($elasticSearchObject, [
                                'delete' => [
                                    '_index' => $index['key'],
                                    '_type'  => 'report',
                                    '_id'    => $accounting['_id']
                                ]
                            ]);
                        }

                        $this->elasticSearch->bulk(ElasticSearch::TYPE_REPORTS, $elasticSearchObject, $index['key']);
                    }
                } while ($data['hits']['total'] > 0);
            }
        }

        return true;
    }

    /**
     * @param Client $client
     * @param $accessPoint
     * @param $createdDate
     */
    private function visitsRegistrationsPerHour(Client $client, $accessPoint, $createdDate)
    {
        $object = [
            "type" => "perhour-reports",
            "friendlyName" => $accessPoint->getFriendlyName(),
            "identifier" => $accessPoint->getIdentifier(),
            "clientId" => $client->getId(),
            "date" => $createdDate->format('Y-m-d'),
            "hour" => $createdDate->format('H:00:00'),
            "totalVisits" => rand(1, 10),
            "totalRegistrations" => 1
        ];

        $index = "report_visits_registrations_per_hour_{$createdDate->format('Y_m')}";
        $this->elasticSearch->index(ElasticSearch::TYPE_REPORTS, $object, null, $index);
    }

    /**
     * @param Client $client
     * @param $accessPoint
     * @param $createdDate
     */
    private function downloadUpload(Client $client, $accessPoint, $createdDate)
    {
        $faker = FakerHelper::faker();

        $object = [
            "type" => "updown-reports",
            "friendlyName" => $accessPoint->getFriendlyName(),
            "identifier" => $accessPoint->getIdentifier(),
            "clientId" => $client->getId(),
            "date" => $createdDate->format('Y-m-d'),
            "download" => $faker->randomNumber(8),
            "upload" => $faker->randomNumber(7),
            "acctinputoctets" => $faker->randomNumber(7),
            "acctoutputoctets" => $faker->randomNumber(7)
        ];

        $index = "report_download_upload_{$createdDate->format('Y_m')}";
        $this->elasticSearch->index(ElasticSearch::TYPE_REPORTS, $object, null, $index);
    }

    /**
     * @param Client $client
     * @param $createdDate
     * @param $guest
     */
    private function visitors(Client $client, $createdDate, $guest)
    {
        $loginValue = $guest->getProperties()[$guest->getLoginField()];
        $name = isset($guest->getProperties()['name']) ? $guest->getProperties()['name'] : 'N/I';
        $index = "report_guests_{$createdDate->format('Y_m')}";

        $hasMultipleVisits = rand(1, 100) <= 30;          // Adjusting the visit count based on a 70/30% probability split.
        $visits = $hasMultipleVisits ? rand(2, 10) : 1;   // It's used to reflect the common scenario in our data

        for ($counter = 0; $counter < $visits; $counter++) {
            $object = [
                "id"            => uniqid(),
                "name"          => $name,
                "loginValue"    => $loginValue,
                "clientId"      => $client->getId(),
                "download"      => rand(0, 80000000),
                "upload"        => rand(0, 20000000),
                "averageTime"   => rand(0, 6000),
                "created"       => $createdDate->format('Y-m-d H:i:s'),
                "lastAccess"    => $createdDate->format('Y-m-d H:i:s'),
                "guestId"       => $guest->getMysql()
            ];
    
            $created = $this->elasticSearch->index(ElasticSearch::TYPE_REPORTS, $object, null, $index);
        }
    }
}
