<?php

namespace Wideti\DomainBundle\Dto;

class ApiBulkResponseDto implements \JsonSerializable
{
    private $hasErrors;
    private $successTotal;
    private $errorsTotal;
    private $errors;

    /**
     * @param bool|false $hasErrors
     * @param array $errors
     * @param int $totalProcessed
     */
    public function __construct($hasErrors = false, $errors = [], $totalProcessed = 0)
    {
        $this->hasErrors = $hasErrors;
        $this->errors = $errors;
        $this->successTotal = $totalProcessed;
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
     * @return bool|false
     */
    public function getHasErrors()
    {
        return $this->hasErrors;
    }

    /**
     * @param bool|false $hasErrors
     */
    public function setHasErrors($hasErrors)
    {
        $this->hasErrors = $hasErrors;
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
     * @return int
     */
    public function getSuccessTotal()
    {
        return $this->successTotal;
    }

    /**
     * @param int $successTotal
     */
    public function setSuccessTotal($successTotal)
    {
        $this->successTotal = $successTotal;
    }

    /**
     * @return mixed
     */
    public function getErrorsTotal()
    {
        return $this->errorsTotal;
    }

    /**
     * @param mixed $errorsTotal
     */
    public function setErrorsTotal($errorsTotal)
    {
        $this->errorsTotal = $errorsTotal;
    }


    public function incrementSuccessTotal()
    {
        $this->successTotal++;
    }

    public function incrementErrorsTotal()
    {
        $this->errorsTotal++;
    }

    public function addError($error)
    {
        $this->errors[] = $error;
    }


}