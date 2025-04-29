<?php

namespace Wideti\DomainBundle\Service\ApiEntityValidator;

class FieldsErrors
{
    private $errors = [];

    /**
     * @param $identifier
     * @param $message
     */
    public function addError($identifier, $message)
    {
        $this->errors[$identifier] = $message;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }
}