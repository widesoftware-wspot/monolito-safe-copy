<?php

namespace Wideti\DomainBundle\Service\WSpotFaker;

use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Repository\ClientRepository;
use Wideti\DomainBundle\Service\AccessPointsGroups\AccessPointsGroupsService;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Helpers\FakerHelper;

class AccessPointGroupFaker implements WSpotFaker
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var AccessPointsGroupsService
     */
    private $accessPointsGroupsService;
    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * AccessPointGroupFaker constructor.
     * @param ConfigurationService $configurationService
     * @param AccessPointsGroupsService $accessPointsGroupsService
     * @param ClientRepository $clientRepository
     */
    public function __construct(
        ConfigurationService $configurationService,
        AccessPointsGroupsService $accessPointsGroupsService,
        ClientRepository $clientRepository
    ) {
        $this->configurationService = $configurationService;
        $this->accessPointsGroupsService = $accessPointsGroupsService;
        $this->clientRepository = $clientRepository;
    }

    public function create(Client $client = null)
    {
        if (!$client) throw new \InvalidArgumentException('Client cannot be null');

        $accessPointGroup = new AccessPointsGroups();
        $accessPointGroup->setClient($client);
        $accessPointGroup->setIsDefault(false);
        $accessPointGroup->setGroupName('Grupo ' . ucfirst(FakerHelper::faker()->colorName));
        $this->accessPointsGroupsService->create($accessPointGroup, true);

        $customItems = [
            [
                'key'   => 'facebook_login',
                'value' => true
            ],
            [
                'key'   => 'facebook_checkin',
                'value' => true
            ],
            [
                'key'   => 'facebook_page_id',
                'value' => '278478838976185'
            ],
            [
                'key'   => 'facebook_checkin_message',
                'value' => 'Estou conectado ao WSpot.'
            ],
            [
                'key'   => 'twitter_login',
                'value' => true
            ],
            [
                'key'   => 'auto_login',
                'value' => true
            ]
        ];

        $this->configurationService->createDefaultConfiguration($client, $accessPointGroup, $customItems);

        return true;
    }

    public function clear(Client $client = null)
    {
        if (!$client) throw new \InvalidArgumentException('Client cannot be null');
        $this->configurationService->removeAllButDefaults($client);
        $this->accessPointsGroupsService->clearByClient($client);
        return true;
    }
}
