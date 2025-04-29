<?php


namespace Wideti\DomainBundle\Service\AccessPointsGroupsConfiguration;

use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\ClientConfiguration;

interface AccessPointsGroupsConfigurationService
{
    /**
     * @param AccessPointsGroups $accessPointsGroup
     * @param array $configurations
     * @param Client $client;
     */
    public function persistAccessPointsGroupsConfigurations ($accessPointsGroup, $configurations, $client);

    /**
     * @param array $configuration
     * @param AccessPointsGroups $accessPointGroup
     * @param Client $client
     * @param int $isDefault
     * @param array $allConf
     * @return ClientConfiguration
     */
    public function handleConfiguration($configuration, $accessPointGroup, $client, $isDefault, $allConf);
}