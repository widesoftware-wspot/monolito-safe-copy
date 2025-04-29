<?php

namespace Wideti\DomainBundle\Service\WSpotFaker;

use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Vendor;
use Wideti\DomainBundle\Helpers\FakerHelper;
use Wideti\DomainBundle\Service\Timezone\TimezoneService;
use Wideti\DomainBundle\Repository\AccessPointsGroupsRepository;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointsService;

class AccessPointFaker implements WSpotFaker
{
    /**
     * @var AccessPointsService
     */
    private $accessPointsService;
    /**
     * @var AccessPointsGroupsRepository
     */
    private $accessPointsGroupsRepository;

    /**
     * AccessPointFaker constructor.
     * @param AccessPointsService $accessPointsService
     * @param AccessPointsGroupsRepository $accessPointsGroupsRepository
     */
    public function __construct(
        AccessPointsService $accessPointsService,
        AccessPointsGroupsRepository $accessPointsGroupsRepository
    ) {
        $this->accessPointsService = $accessPointsService;
        $this->accessPointsGroupsRepository = $accessPointsGroupsRepository;
    }

    public function create(Client $client = null)
    {
        if (!$client) throw new \InvalidArgumentException('Client cannot be null');

        /**
         * @var $group AccessPointsGroups
         */
        $groups = $this->accessPointsGroupsRepository->findBy([
            'isDefault' => false,
            'client' => $client
        ]);


        $accessPoint = new AccessPoints();
        $accessPoint->setClient($client);
        $accessPoint->setStatus(AccessPoints::ACTIVE);
        $accessPoint->setFriendlyName('Ponto de acesso ' . FakerHelper::faker()->colorName);
        $accessPoint->setVendor(Vendor::MIKROTIK);
        $accessPoint->setIdentifier(str_replace(':', '-', FakerHelper::faker()->macAddress));
        $accessPoint->setRadiusVerified(true);
        $accessPoint->setRequestVerified(true);
        $accessPoint->setTimezone(TimezoneService::DEFAULT_TIMEZONE);

        try {
            $this->accessPointsService->create($accessPoint, $groups[array_rand($groups, 1)]);
        } catch (\Exception $e) {
            throw new $e;
        }

        return true;
    }

    public function clear(Client $client = null)
    {
        if (!$client) throw new \InvalidArgumentException('Client cannot be null');

        $this->accessPointsService->clearByClient($client);
        return true;
    }
}
