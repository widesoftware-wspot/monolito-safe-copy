<?php

namespace Wideti\DomainBundle\Validator;

class CustomFieldsValidate
{
    private $properties;

    public function getProperties()
    {
        return $this->properties;
    }

    public function validate()
    {
        return false;
    }
}