<?php

namespace Wideti\DomainBundle\Service\ApiEntityValidator\CustomFieldValidator\Rules;

class CustomDate implements Rule
{

    public function validate(array $fieldValidation, $entityValue, $locale = "pt_br")
    {

        if (!in_array($locale, $fieldValidation['locale'])) {
            return true;
        }

        $date = str_replace('/', '-', $entityValue);
        $time = strtotime($date);

        return $time != false;
    }
}