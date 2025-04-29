<?php

namespace Wideti\DomainBundle\Service\Segmentation\Filter;

class FilterItem
{
    const IS                = 'Is';
    const IS_NOT            = 'IsNot';
    const CONTAINS          = 'Contains';
    const DOES_NOT_CONTAIN  = 'DoesNotContain';
    const GREATER_THAN      = 'GreaterThan';
    const LESS_THAN         = 'LessThan';
    const DIFFERENT         = 'Different';
    const BEFORE_THEN       = 'BeforeThen';
    const AFTER_THEN        = 'AfterThen';
    const EXACLTY           = 'Exaclty';
    const RANGE             = 'Range';
    const LAST_WEEK         = 'LastWeek';
    const LAST_MONTH        = 'LastMonth';

    const TYPE_TEXT         = 'text';
    const TYPE_SELECT       = 'select';
    const TYPE_DATE         = 'date';
    const TYPE_INTEGER      = 'integer';
    const TYPE_BOOLEAN      = 'boolean';

    private $identifier;
    private $equality;
    private $type;
    private $value;
    private $isCompleted = false;

    /**
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param mixed $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return mixed
     */
    public function getEquality()
    {
        return $this->equality;
    }

    /**
     * @param mixed $equality
     */
    public function setEquality($equality)
    {
        $this->equality = $equality;
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
    public function isCompleted()
    {
        return $this->isCompleted;
    }

    /**
     * @param mixed $isCompleted
     */
    public function setIsCompleted($isCompleted)
    {
        $this->isCompleted = $isCompleted;
    }
}
