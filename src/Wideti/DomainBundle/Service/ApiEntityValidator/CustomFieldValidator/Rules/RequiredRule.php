<?php

namespace Wideti\DomainBundle\Service\ApiEntityValidator\CustomFieldValidator\Rules;

class RequiredRule implements Rule
{

    public function validate(array $fieldValidation, $entityValue, $locale = "pt_br")
    {
        if (!in_array($locale, $fieldValidation['locale'])) {
            return true;
        }

        if ($fieldValidation['value']) {
            return !empty($entityValue);
        }

        return true;
    }
}