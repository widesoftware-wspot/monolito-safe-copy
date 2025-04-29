<?php

namespace Wideti\DomainBundle\Service\WSpotFaker;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Document\Repository\GuestRepository;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\FakerHelper;
use Wideti\DomainBundle\Repository\AccessPointsRepository;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\RadacctRepository;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;

class AccountingFaker implements WSpotFaker
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
     * AccountingFaker constructor.
     * @param GuestRepository $guestRepository
     * @param AccessPointsRepository $accessPointsRepository
     * @param ElasticSearch $elasticSearch
     * @param RadacctRepository $radacctRepository
     */
    public function __construct(
        GuestRepository $guestRepository,
        AccessPointsRepository $accessPointsRepository,
        ElasticSearch $elasticSearch,
        RadacctRepository $radacctRepository
    ) {
        $this->guestRepository = $guestRepository;
        $this->accessPointsRepository = $accessPointsRepository;
        $this->elasticSearch = $elasticSearch;
        $this->radacctRepository = $radacctRepository;
    }

    public function create(Client $client = null, $guests = [])
    {
        if (!$client) throw new \InvalidArgumentException('Client cannot be null');

        $faker = FakerHelper::faker();
        $accountings = [];


        /**
         * @var $guests Guest
         * @var $guest Guest
         */
        foreach ($guests as $guest) {
            /**
             * @var $createdDate \DateTime
             * @var $customDate \DateTime
             */
            if ($guest->getCreated() instanceof \DateTime) {
                $createdDate = $guest->getCreated();
            } else {
                $createdDate = new \DateTime(date('Y-m-d H:i:s', $guest->getCreated()->sec));
            }

            $randomDigit    = rand(1, 100);
            $customDate     = clone $createdDate;
            $customDate     = $customDate->modify("+{$randomDigit}minute");

            $acctStatTime   = $createdDate->format('Y-m-d H:i:s');
            $acctStopTime   = $customDate->format('Y-m-d H:i:s');
            $interimUpdate  = $customDate->format('Y-m-d H:i:s');

            /**
             * @var $accessPoint AccessPoints
             */
            $accessPoint        = $this->accessPointsRepository->findOneBy(['identifier' => $guest->getRegistrationMacAddress()]);
            $calledStationName  = $accessPoint ? $accessPoint->getFriendlyName() : 'AA-BB-CC-11-22-33';

            $accounting = [
                "client_id"             => $client->getId(),
                "username"              => $guest->getMysql(),
                "employee"              => false,
                "acctsessionid"         => "{$faker->randomNumber(8)}-000014A2",
                "acctuniqueid"          => $faker->md5,
                "nasipaddress"          => $faker->ipv4,
                "acctinputoctets"       => "{$faker->randomNumber(7)}00",
                "acctoutputoctets"      => "{$faker->randomNumber(7)}00",
                "download"              => "{$faker->randomNumber(7)}00",
                "upload"                => "{$faker->randomNumber(6)}00",
                "callingstationid"      => $accessPoint->getIdentifier(),
                "framedipaddress"       => $faker->ipv4,
                "calledstation_name"    => $calledStationName,
                "calledstationid"       => $guest->getRegistrationMacAddress(),
                "acctstarttime"         => $acctStatTime,
                "interim_update"        => $interimUpdate,
                "timezone"              => $faker->timezone
            ];

            $percentageChanceOfBeenOnline = 10;
            if (rand(0, 100) <= $percentageChanceOfBeenOnline) {
                $startTime = $faker->dateTimeBetween('-4 hours', '-10 minutes');
                $accounting["acctstarttime"] = $startTime->format('Y-m-d H:i:s');;
            } else {
                $accounting["acctstoptime"] = $acctStopTime;
            }

            $index = "wspot_{$createdDate->format('Y_m')}";
            $this->elasticSearch->index(ElasticSearch::TYPE_RADACCT, $accounting, null, $index);
        }

        return true;
    }

    public function clear(Client $client = null)
    {
        if (!$client) throw new \InvalidArgumentException('Client cannot be null');

        $indexes = $this->radacctRepository->getAllIndexes($client->getId());

        foreach ($indexes as $index) {
            do {
                $elasticSearchObject = [];

                $search = [
                    "size" => 10000,
                    "query" => [
                        "term" => [
                            "client_id" => $client->getId()
                        ]
                    ]
                ];

                $accountings = $this->elasticSearch->search('radacct', $search, $index['key']);

                if ($accountings['hits']['total'] > 0) {
                    foreach ($accountings['hits']['hits'] as $accounting) {
                        array_push($elasticSearchObject, [
                            'delete' => [
                                '_index' => $index['key'],
                                '_type'  => 'radacct',
                                '_id'    => $accounting['_id']
                            ]
                        ]);
                    }

                    $this->elasticSearch->bulk(ElasticSearch::TYPE_RADACCT, $elasticSearchObject, $index['key']);
                }
            } while ($accountings['hits']['total'] > 0);
        }

        return true;
    }
}
