<?php

namespace Wideti\DomainBundle\Service\FirstConfig;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\FirstConfig\Dto\FieldFirstConfigDTO;
use Wideti\DomainBundle\Service\FirstConfig\Dto\FirstConfigResponse;
use Wideti\DomainBundle\Service\FirstConfig\Dto\FirstConfigurationParameters;

interface FirstConfigService
{
    /**
     * @param Client $client
     * @return array|FieldFirstConfigDTO
     */
    function getTemplateFields(Client $client);

    /**
     * @param FirstConfigurationParameters $configParameters
     * @param Client $client
     * @return FirstConfigResponse
     * @throws
     */
    public function processConfigParameters(FirstConfigurationParameters $configParameters, Client $client);
}