<?php

namespace Wideti\DomainBundle\Helpers\CustomField\CustomFieldFaker;

use Faker\Factory;

class PhoneFaker implements CustomFieldFakerHelper
{
    public function generate($locale = 'pt_BR', $params = null)
    {
        $faker = Factory::create($locale);
        return "19{$faker->cellphone(false, true)}";
    }
}
