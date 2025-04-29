<?php

namespace Wideti\DomainBundle\Document\Group\Builder;

use Wideti\DomainBundle\Document\Group\ConfigurationValue;

class GroupConfigurationValuesBuilder
{
    /**
     * @var ConfigurationValue
     */
    private $configurationValue;

    public function __construct()
    {
        $this->configurationValue = new ConfigurationValue();
    }

    public function withKey($key)
    {
        $this->configurationValue->setKey($key);
        return $this;
    }

    public function withValue($value)
    {
        $this->configurationValue->setValue($value);
        return $this;
    }

    public function withType($type)
    {
        $this->configurationValue->setType($type);
        return $this;
    }

    public function withLabel($label)
    {
        $this->configurationValue->setLabel($label);
        return $this;
    }

    public function withTip($tip)
    {
        $this->configurationValue->setTip($tip);
        return $this;
    }

    public function withParams(array $params)
    {
        $this->configurationValue->setParams($params);
        return $this;
    }

    public function build()
    {
        return $this->configurationValue;
    }
}
