<?php

namespace Wideti\DomainBundle\Document\Group;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument()
 */
class Configuration
{
    const BLOCK_PER_TIME    = 'block_per_time';
    const VALIDITY_ACCESS   = 'validity_access';
    const BANDWIDTH         = 'bandwidth';

    /**
     * @ODM\String()
     */
    private $category;

    /**
     * @ODM\String()
     */
    private $shortcode;

    /**
     * @ODM\String()
     */
    private $description;

    /**
     * @ODM\EmbedMany(targetDocument="ConfigurationValue")
     */
    private $configurationValues = [];

    public function getConfigurationValueByKey($key)
    {
        /**
         * @var ConfigurationValue $value
         */
        foreach ($this->configurationValues as $value) {
            if ($value->getKey() === $key) {
                return $value;
            }
        }
        return false;
    }

    public function addConfigurationValues(ConfigurationValue $configurationValue)
    {
        $this->configurationValues[] = $configurationValue;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return ConfigurationValue[]
     */
    public function getConfigurationValues()
    {
        return $this->configurationValues;
    }

    /**
     * @param mixed $configurationValues
     */
    public function setConfigurationValues($configurationValues)
    {
        $this->configurationValues = $configurationValues;
    }

    /**
     * @return mixed
     */
    public function getShortcode()
    {
        return $this->shortcode;
    }

    /**
     * @param mixed $shortcode
     */
    public function setShortcode($shortcode)
    {
        $this->shortcode = $shortcode;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
}
