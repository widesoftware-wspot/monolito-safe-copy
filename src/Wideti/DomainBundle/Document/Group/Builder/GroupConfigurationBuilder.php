<?php

namespace Wideti\DomainBundle\Document\Group\Builder;

use Wideti\DomainBundle\Document\Group\Configuration;
use Wideti\DomainBundle\Document\Group\ConfigurationValue;

class GroupConfigurationBuilder
{
    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct()
    {
        $this->configuration = new Configuration();
    }

    public function withCategory($category)
    {
        $this->configuration->setCategory($category);
        return $this;
    }

    public function withShortcode($shortcode)
    {
        $this->configuration->setShortcode($shortcode);
        return $this;
    }

    public function withDescription($description)
    {
        $this->configuration->setDescription($description);
        return $this;
    }

    public function addConfigurarionValue(ConfigurationValue $configurationValue)
    {
        $this->configuration->addConfigurationValues($configurationValue);
        return $this;
    }

    public function build()
    {
        return $this->configuration;
    }
}
