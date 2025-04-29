<?php

namespace Wideti\DomainBundle\Helpers;

use Faker\Factory;

class FakerHelper
{
    public static function faker()
    {
        return Factory::create('pt_BR');
    }

    public static function existsFields($fields = [], $neededFields = []) {
        $resultArray = array_filter($fields, function($field) use ($neededFields) {
            /** @var Field $field */
            return in_array($field->getIdentifier(), $neededFields);
        });

        return !empty($resultArray);
    }
}
