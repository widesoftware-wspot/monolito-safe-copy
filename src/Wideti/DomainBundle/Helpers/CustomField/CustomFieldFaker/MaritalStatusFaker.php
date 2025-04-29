<?php

namespace Wideti\DomainBundle\Helpers\CustomField\CustomFieldFaker;

use Faker\Factory;

class MaritalStatusFaker implements CustomFieldFakerHelper
{
    public function generate($locale = 'pt_BR', $params = null)
    {
        $faker = Factory::create($locale);
        return $faker->randomElement(['Solteiro(a)', 'Noivo(a)', 'Casado(a)', 'Divorciado(a)']);
    }
}
