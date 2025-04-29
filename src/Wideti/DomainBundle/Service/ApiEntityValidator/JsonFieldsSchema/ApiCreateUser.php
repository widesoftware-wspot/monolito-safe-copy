<?php

namespace Wideti\DomainBundle\Service\ApiEntityValidator\JsonFieldsSchema;

class ApiCreateUser implements \JsonSerializable, ApiSchema
{
    private $fields = [];
    private $properties = [];

    /**
     * @return mixed
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param mixed $fields
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return mixed
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param mixed $properties
     */
    public function setProperties(array $properties)
    {
        $this->properties = $properties;
    }


    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    /**
     * @return int
     */
    public function countFieldsLeft()
    {
        return count($this->fields) + count($this->properties);
    }

    /**
     * @return array
     */
    public function getAllFieldsLeft()
    {
        return array_merge($this->fields, $this->properties);
    }
}
