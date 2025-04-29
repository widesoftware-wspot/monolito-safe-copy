<?php

namespace Wideti\DomainBundle\Helpers\CustomField\CustomFieldFaker;

use Faker\Factory;

class UfFaker implements CustomFieldFakerHelper
{
    public function generate($locale = 'pt_BR', $params = null)
    {
        $faker = Factory::create($locale);
        return $faker->countryCode;
    }
}
