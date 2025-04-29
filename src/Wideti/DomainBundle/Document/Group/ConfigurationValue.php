<?php

namespace Wideti\DomainBundle\Document\Group;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument()
 */
class ConfigurationValue
{
    const BLOCK_PER_TIME    = 'enable_block_per_time';
    const VALIDITY_ACCESS   = 'enable_validity_access';
    const BANDWIDTH         = 'enable_bandwidth';

    /**
    * @ODM\String()
    */
    private $key;

    /**
     * @ODM\String()
     */
    private $value;

    /**
     * @ODM\String()
     */
    private $type;

    /**
     * @ODM\Hash()
     */
    private $params;

    /**
     * @ODM\String()
     */
    private $label;

    /**
     * @ODM\String()
     */
    private $tip;

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param mixed $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param mixed $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param mixed $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return mixed
     */
    public function getTip()
    {
        return $this->tip;
    }

    /**
     * @param mixed $tip
     */
    public function setTip($tip)
    {
        $this->tip = $tip;
    }
}
