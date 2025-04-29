<?php

namespace Wideti\DomainBundle\Service\Configuration;

trait ConfigurationServiceAware
{
    /**
     * @var ConfigurationServiceAware
     */
    protected $config;

    public function setConfigurationService(ConfigurationService $configurationService)
    {
        $this->config = $configurationService;
    }
}
