<?php

namespace Wideti\DomainBundle\Service\ApiEntityValidator;

use Wideti\DomainBundle\Service\ApiEntityValidator\JsonFieldsSchema\ApiSchema;

class ApiErrors implements \JsonSerializable
{
    private $message;
    private $status;
    private $missingFields;
    private $time;
    private $entityError;

    /**
     * @var array
     */
    private $errors = [];

    public function __construct()
    {
        $this->time = date("Y-m-d H:i");
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param array $errors
     */
    public function setErrors($errors)
    {
        $this->errors = $errors;
    }

    /**
     * @return mixed
     */
    public function getTime()
    {
        return $this->time;
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

    public function addErrors(array $error)
    {
        $this->errors += $error;
    }

    /**
     * @return mixed
     */
    public function getEntityError()
    {
        return $this->entityError;
    }

    /**
     * @param \JsonSerializable $entityError
     */
    public function setEntityError(\JsonSerializable $entityError)
    {
        $this->entityError = $entityError;
    }

    /**
     * @return mixed
     */
    public function getMissingFields()
    {
        return $this->missingFields;
    }

    /**
     * @param ApiSchema $missingFields
     */
    public function setMissingFields(ApiSchema $missingFields)
    {
        $this->missingFields = $missingFields;
    }
}
