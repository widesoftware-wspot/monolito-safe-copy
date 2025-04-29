<?php

namespace Wideti\DomainBundle\Service\ExpirationTime;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Guests;
use Wideti\DomainBundle\Entity\Radcheck;
use Wideti\DomainBundle\Helpers\ConvertStringToSecond;
use Wideti\DomainBundle\Repository\RadcheckRepository;
use Wideti\DomainBundle\Service\Timezone\TimezoneService;

class ExpirationTimeImp implements ExpirationTime
{
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var RadcheckRepository
     */
    private $radcheckRepository;

    /**
     * ExpirationTimeImp constructor.
     * @param EntityManager $entityManager
     * @param RadcheckRepository $radcheckRepository
     */
    public function __construct(
        EntityManager $entityManager,
        RadcheckRepository $radcheckRepository
    ) {
        $this->entityManager = $entityManager;
        $this->radcheckRepository = $radcheckRepository;
    }

    public function get(Client $client, Guest $guest)
    {
        /**
         * @var Radcheck $expiration
         */
        $expiration = $this->radcheckRepository->findOneBy(
            [
                'client'    => $client,
                'guest'     => $guest->getMysql(),
                'attribute' => 'Expiration'
            ]
        );

        if (!$expiration) return null;

        return $expiration;
    }

    public function create(
        Client $client,
        Guests $guest,
        Carbon $expirationTime,
        $apTimezone = TimezoneService::DEFAULT_TIMEZONE,
        $groupId = null,
        $expirationDate = null
    ) {
        $radcheck = new Radcheck();
        $radcheck->setAttribute('Expiration');
        $radcheck->setOp(':=');
        $radcheck->setClient($client);
        $radcheck->setGuest($guest);
        $expirationDate = ($expirationDate != null) ? $expirationDate : $expirationTime->format('F j Y H:i:s');
        $radcheck->setValue($expirationDate);
        $radcheck->setApTimezone($apTimezone);
        if(!empty($groupId)){
            $radcheck->setGroupId($groupId);
        }
        $this->entityManager->persist($radcheck);
        $this->entityManager->flush();
    }

    public function update(
        Client $client,
        Guests $guest,
        Radcheck $expirationTime,
        Carbon $newExpirationTime,
        $apTimezone = TimezoneService::DEFAULT_TIMEZONE,
        $groupId = null
    ) {
        $expirationTime->setValue($newExpirationTime->format('F j Y H:i:s'));
        $expirationTime->setApTimezone($apTimezone);
        if (!empty($groupId)) {
            $expirationTime->setGroupId($groupId);
        }

        $this->entityManager->persist($expirationTime);
        $this->entityManager->flush();
    }

    public function isTimeExpired(Carbon $radcheckTimeLeft, Carbon $nextLogin)
    {
        $now = Carbon::now(TimezoneService::UTC);

        if (
            ($now->format('Y-m-d H:i:s') > $radcheckTimeLeft->format('Y-m-d H:i:s')) &&
            ($now->format('Y-m-d H:i:s') < $nextLogin->format('Y-m-d H:i:s'))
        ) {
            return true;
        }
        return false;
    }

    public function getNextLogin(Carbon $radcheckTimeLeft, $period, $time)
    {
        $period = $radcheckTimeLeft->add(
            new \DateInterval(
                ConvertStringToSecond::convertPeriod($period)
            )
        );

        return $period->sub(
            new \DateInterval(
                ConvertStringToSecond::convertPeriod($time)
            )
        );
    }

    public function UTCToTimezoneBased(Carbon $date, $timezone)
    {
        return $date->tz($timezone);
    }

    public function convertTime($time)
    {
        $value = strtoupper($time);
        $value = str_replace(['D', 'H', 'M', 'S'], [' DAY', ' HOUR', ' MINUTE', ' SECOND'], $value);
        return date('F j Y H:i:s', strtotime('+'.$value));
    }

    public function expiredAccessCode( Client $client , Guest $guest)
    {
        $radcheckTimeLeft = $this->get( $client, $guest);
        if (is_null($radcheckTimeLeft)){
            return true;
        }
        $radcheckTimeLeft = $radcheckTimeLeft->getValue();
        $date = Carbon::createFromFormat('F j Y H:i:s', $radcheckTimeLeft);
        $now = Carbon::now(TimezoneService::UTC);

        if ($now->format('Y-m-d H:i:s') > $date->format('Y-m-d H:i:s')) {
           return true;
        }
        return false;
    }
}