<?php

namespace Wideti\DomainBundle\Service\ApiEntityValidator\CustomFieldValidator\Rules;

interface Rule
{
    public function validate(array $fieldValidation, $entityValue, $locale = "pt_br");
}