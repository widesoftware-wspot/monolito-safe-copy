<?php

namespace Wideti\DomainBundle\Service\Configuration;

use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\Configuration\Dto\FacebookConfigurationDto;
use Wideti\FrontendBundle\Factory\Nas;

interface ConfigurationService
{
	public function getGroups($groupIdParent);
	public function getConfigAsMap($configuration, $domain);
	public function getConfigByKey(Client $client, $key);
	public function getByGroupId($groupId);
    public function getConfigurationMap(Nas $nas = null, Client $client);
    public function saveConfiguration(array $items, AccessPointsGroups $accessPointsGroups);
    public function deleteExpiration(Client $client);
    public function deleteExpirationByGuestGroup($clientId, $groupId);
    public function updateKey($key, $value, Client $client);
    public function get(Nas $nas = null, $client, $key);

    /**
     * @param Nas|null $nas
     * @param Client $client
     * @return FacebookConfigurationDto
     */
    public function getFacebookConfiguration(Nas $nas = null, Client $client);
    public function getCacheKey($identifier, $prefix = "");
    public function createDefaultConfiguration(Client $client, $group = null, $customItems = null, $aditionalInfo = []);
    public function removeAllButDefaults(Client $client);
    public function removeByGroup(Client $client, $groupId);
    public function getDefaultConfiguration(Client $client);
    public function isMacAlreadyRegistered($mac);
    public function isUniqueMacEnabled(Client $client, $mac);

    /**
     * @param string $apIdentifier
     * @param Client $client
     * @return array
     */
    public function getByIdentifierOrDefault($apIdentifier, Client $client);

    /**
     * @param AccessPointsGroups $accessPointsGroups
     * @return array
     */
    public function getByAccessPointGroup(AccessPointsGroups $accessPointsGroups);

    public function setOnSession($from, $configMap);

    public function getGoogleClient($domainOfloggedClient = null);
}
