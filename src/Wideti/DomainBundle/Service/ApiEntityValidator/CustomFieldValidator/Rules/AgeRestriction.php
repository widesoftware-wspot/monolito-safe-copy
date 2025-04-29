<?php

namespace Wideti\DomainBundle\Service\ApiEntityValidator\CustomFieldValidator\Rules;

class AgeRestriction implements Rule
{
    public function validate(array $fieldValidation, $entityValue, $locale = "pt_br")
    {
        if (!in_array($locale, $fieldValidation['locale'])) {
            return true;
        }

        $birthdate = \DateTime::createFromFormat('d-m-Y', $entityValue);
        $ageYears = $birthdate->diff(new \DateTime())->y;

        return $ageYears >= 18;
    }
}