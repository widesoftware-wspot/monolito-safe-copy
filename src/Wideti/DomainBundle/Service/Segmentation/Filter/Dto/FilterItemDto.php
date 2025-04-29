<?php

namespace Wideti\DomainBundle\Service\Segmentation\Filter\Dto;

class FilterItemDto
{
    /** @var string */
    private $identifier;
    /** @var string */
    private $equality;
    /** @var string */
    private $type;
    /** @var string */
    private $value;
    /** @var boolean */
    private $isCompleted = false;

    /**
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param $identifier
     * @return mixed
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEquality()
    {
        return $this->equality;
    }

    /**
     * @param $equality
     * @return $this
     */
    public function setEquality($equality)
    {
        $this->equality = $equality;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $type
     * @return $this,
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return mixed
     */
    public function isCompleted()
    {
        return $this->isCompleted;
    }

    /**
     * @param $isCompleted
     * @return $this
     */
    public function setIsCompleted($isCompleted)
    {
        $this->isCompleted = $isCompleted;
        return $this;
    }
}
