<?php

namespace Wideti\DomainBundle\Service\ApiEntityValidator\CustomFieldValidator\Rules;

class MinlengthRule implements Rule
{

    public function validate(array $fieldValidation, $entityValue, $locale = "pt_br")
    {
        if (!in_array($locale, $fieldValidation['locale'])) {
            return true;
        }

        $min = $fieldValidation['value'];
        return strlen($entityValue) >= $min;
    }
}