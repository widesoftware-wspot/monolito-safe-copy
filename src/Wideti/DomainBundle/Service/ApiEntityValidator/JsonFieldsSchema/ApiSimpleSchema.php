<?php

namespace Wideti\DomainBundle\Service\ApiEntityValidator\JsonFieldsSchema;

class ApiSimpleSchema implements \JsonSerializable, ApiSchema
{
    private $fields = [];

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
        return count($this->fields);
    }

    /**
     * @return array
     */
    public function getAllFieldsLeft()
    {
        return $this->fields;
    }
}
