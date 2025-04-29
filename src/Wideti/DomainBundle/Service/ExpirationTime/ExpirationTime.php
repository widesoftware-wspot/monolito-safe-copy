<?php

namespace Wideti\DomainBundle\Service\ExpirationTime;

use Carbon\Carbon;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Guests;
use Wideti\DomainBundle\Entity\Radcheck;
use Wideti\DomainBundle\Service\Timezone\TimezoneService;

interface ExpirationTime
{

    public function get(Client $client, Guest $guest);

    public function create(
        Client $client,
        Guests $guest,
        Carbon $expirationTime,
        $apTimezone = TimezoneService::DEFAULT_TIMEZONE
    );

    public function update(
        Client $client,
        Guests $guest,
        Radcheck $expirationTime,
        Carbon $newExpirationTime,
        $apTimezone = TimezoneService::DEFAULT_TIMEZONE
    );

    public function isTimeExpired(Carbon $radcheckTimeLeft, Carbon $nextLogin);

    public function getNextLogin(Carbon $radcheckTimeLeft, $period, $time);

    public function UTCToTimezoneBased(Carbon $date, $timezone);

    public function convertTime($time);

    public function expiredAccessCode(Client $client, Guest $guest);
}