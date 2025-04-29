<?php

namespace Wideti\DomainBundle\Service\WSpotFaker;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\WspotFaker\GuestFaker;
use Wideti\DomainBundle\Service\WspotFaker\ReportsFaker;
use Wideti\DomainBundle\Service\WspotFaker\AccountingFaker;

class WSpotFakerManager implements WSpotFaker
{
    /**
     * @var WSpotFaker[]
     */
    private $wspotFakers;

    public function create(Client $client = null)
    {
        if (!$client) throw new \InvalidArgumentException('Client cannot be null');

        $newGuests = [];
        foreach ($this->wspotFakers as $faker) {
            if ($faker instanceof ReportsFaker || $faker instanceof AccountingFaker) {
                $result = $faker->create($client, $newGuests);
            } else {
                $result = $faker->create($client);
            }

            if ($faker instanceof GuestFaker) {
                $newGuests = $result;
            }
        }
    }

    public function clear(Client $client = null)
    {
        if (!$client) throw new \InvalidArgumentException('Client cannot be null');

        foreach ($this->wspotFakers as $faker) {
            $faker->clear($client);
        }
    }

    public function registryWSpotFaker(WSpotFaker $faker)
    {
        $this->wspotFakers[] = $faker;
    }
}
