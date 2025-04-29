<?php

namespace Wideti\DomainBundle\Service\Configuration;

trait ConfigurationAware
{
    /**
     * @var ConfigurationAware
     */
    protected $config;

    public function setConfigurations(ConfigurationService $configurationService)
    {
        $this->config = $configurationService;
    }
}
