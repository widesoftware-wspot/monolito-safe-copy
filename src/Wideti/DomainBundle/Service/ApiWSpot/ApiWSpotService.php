<?php

namespace Wideti\DomainBundle\Service\ApiWSpot;

use Wideti\DomainBundle\Entity\ApiWSpot;
use Wideti\DomainBundle\Entity\Client;

interface ApiWSpotService
{
    public function create(ApiWSpot $token, $resourceNames);
    public function createSegmentationTokenViaBluePanel(Client $client);
    public function update(ApiWSpot $token, $resourceNames);
    public function delete(ApiWSpot $token);
    public function generateToken();
    public function createRole(ApiWSpot $token, $rolePermission);
    public function createResourcePermission(ApiWSpot $token, array $resourceNames);
    public function createContract(ApiWSpot $token);
    public function getTokenByResourceName(Client $client, $resourceName);
}
