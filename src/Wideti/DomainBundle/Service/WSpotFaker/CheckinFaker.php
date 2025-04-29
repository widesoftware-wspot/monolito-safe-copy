<?php

namespace Wideti\DomainBundle\Service\WSpotFaker;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Document\Repository\GuestRepository;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;

class CheckinFaker implements WSpotFaker
{
    private $guestRepository;
    private $elasticSearch;

    /**
     * CheckinFaker constructor.
     * @param GuestRepository $guestRepository
     * @param ElasticSearch $elasticSearch
     */
    public function __construct(
        GuestRepository $guestRepository,
        ElasticSearch $elasticSearch
    ) {
        $this->guestRepository = $guestRepository;
        $this->elasticSearch = $elasticSearch;
    }

    public function create(Client $client = null)
    {
        if (!$client) throw new \InvalidArgumentException('Client cannot be null');

        $guests = $this
            ->guestRepository
            ->findAll();

        /**
         * @var $guests Guest
         * @var $guest Guest
         */
        foreach ($guests as $guest) {
            /**
             * @var $date \DateTime
             */
            if ($guest->getCreated() instanceof  \DateTime) {
                $date = $guest->getCreated();
            } else {
                $date = new \DateTime(date('Y-m-d H:i:s', $guest->getCreated()->sec));
            }

            $checkin = [
                "client_id"     => $client->getId(),
                "guest_id"      => $guest->getMysql(),
                "apMacAddress"  => $guest->getRegistrationMacAddress(),
                "page_id"       => "278478838976185",
                "date"          => $date->format('Y-m-d H:i:s')
            ];

            $index  = "checkins_{$date->format('Y')}";

            $this->elasticSearch->index('logs', $checkin, null, $index);
        }

        return true;
    }

    public function clear(Client $client = null)
    {
        if (!$client) throw new \InvalidArgumentException('Guest cannot be null');

        $elasticSearchObject = [];

        $search = [
            "size" => 10000,
            "query" => [
                "term" => [
                    "client_id" => $client->getId()
                ]
            ]
        ];

        $index = "checkins_" . date('Y');

        $checkins = $this->elasticSearch->search('logs', $search, $index);

        if ($checkins['hits']['total'] > 0) {
            foreach ($checkins['hits']['hits'] as $checkin) {
                array_push($elasticSearchObject, [
                    'delete' => [
                        '_index' => $index,
                        '_type'  => 'logs',
                        '_id'    => $checkin['_id']
                    ]
                ]);
            }

            $this->elasticSearch->bulk('logs', $elasticSearchObject, $index);
        }

        return true;
    }
}
