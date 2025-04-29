<?php

namespace Wideti\DomainBundle\Service\WSpotFaker;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Document\Repository\GuestRepository;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Vendor;
use Wideti\DomainBundle\Repository\AccessPointsRepository;
use Wideti\DomainBundle\Repository\SmsHistoricRepository;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsService;
use Wideti\DomainBundle\Service\Guest\GuestService;
use Wideti\DomainBundle\Helpers\FakerHelper;

class GuestFaker implements WSpotFaker
{
    /**
     * @var GuestService
     */
    private $guestService;
    /**
     * @var SmsHistoricRepository
     */
    private $smsHistoricRepository;
    /**
     * @var AccessPointsRepository
     */
    private $accessPointsRepository;
    /**
     * @var GuestRepository
     */
    private $guestRepository;
    /**
     * @var CustomFieldsService
     */
    private $customFieldsService;

    /**
     * GuestFaker constructor.
     * @param GuestService $guestService
     * @param GuestRepository $guestRepository
     * @param SmsHistoricRepository $smsHistoricRepository
     * @param AccessPointsRepository $accessPointsRepository
     * @param CustomFieldsService $customFieldsService
     */
    public function __construct(
        GuestService $guestService,
        GuestRepository $guestRepository,
        SmsHistoricRepository $smsHistoricRepository,
        AccessPointsRepository $accessPointsRepository,
        CustomFieldsService $customFieldsService
    ) {
        $this->guestService = $guestService;
        $this->smsHistoricRepository = $smsHistoricRepository;
        $this->accessPointsRepository = $accessPointsRepository;
        $this->guestRepository = $guestRepository;
        $this->customFieldsService = $customFieldsService;
    }

    public function create(Client $client = null)
    {
        if (!$client) throw new \InvalidArgumentException('Client cannot be null');

        $faker = FakerHelper::faker();
        $customFields = $this->customFieldsService->getCustomFields();
        $loginField = $this->customFieldsService->getLoginFieldIdentifier();

        $accessPoints = $this
        ->accessPointsRepository
        ->findBy([
            'client' => $client
        ]);

        $guests = [];
        $guestsCounter = rand(80, 100);
        for ($counter=0; $counter<$guestsCounter; $counter++) {
            $dateTime = $this->generateBiasRecentData();
            $accessPoint = $accessPoints[array_rand($accessPoints, 1)];

            $lastAccessTimeDrift = rand(1, 24);
            $dateTimeLastAccess = clone $dateTime;
            $dateTimeLastAccess->modify("+{$lastAccessTimeDrift} hours");

            $mongoDate = new \MongoDate($dateTime->getTimestamp());
            $dateTimeLastAccess = new \MongoDate($dateTimeLastAccess->getTimestamp());

            $guest = new Guest();
            $guestData = WSpotFakerService::generateValueToCustomFields($customFields);
            $guest->setProperties($guestData);

            $guest->setGroup('guest');
            $guest->setPassword($faker->password(6, 6));
            $guest->setAuthorizeEmail(true);
            $guest->setEmailIsValid(true);
            $guest->setCreated($mongoDate);
            $guest->setLastAccess($dateTimeLastAccess);
            $guest->setLocale('pt_br');
            $guest->setNasVendor(Vendor::MIKROTIK);
            $guest->setRegistrationMacAddress($accessPoint);
            $guest->setRegisterMode(Guest::getRandomRegisterMode());
            $guest->setReturning(true);
            $guest->setStatus(Guest::STATUS_ACTIVE);
            $guest->setLoginField($loginField);
            $guests[] = $guest;

            try {
                $this->guestService->createByAdmin($guest, true, false);
            } catch (\Exception $e) {
                continue;
            }
        }

        return $guests;
    }

    public function clear(Client $client = null)
    {
        if (!$client) throw new \InvalidArgumentException('Client cannot be null');
        $guests = $this->guestRepository->findAll();
        $this->smsHistoricRepository->deleteByClient($client);

        $client = str_replace('.', '-', $client->getDomain());
        $this->guestService->deleteAllByClient($client);
        return true;
    }

    public function generateBiasRecentData() 
    {
        $faker = FakerHelper::faker();
        $randomValue = rand(1, 100);
        $cumulativeWeight = 0;
    
        $dateRanges = [
            ['weight' => 10, 'start' => '-100 days', 'end' => '-67 days'],
            ['weight' => 10, 'start' => '-66 days', 'end' => '-34 days'],
            ['weight' => 10, 'start' => '-33 days', 'end' => '-8 days'],
            ['weight' => 20, 'start' => '-7 days', 'end' => '-4 days'],
            ['weight' => 50, 'start' => '-3 days', 'end' => 'now'],
        ];

        foreach ($dateRanges as $range) {
            $cumulativeWeight += $range['weight'];
            if ($randomValue <= $cumulativeWeight) {
                return $faker->dateTimeBetween($range['start'], $range['end']);
            }
        }
    }
}
