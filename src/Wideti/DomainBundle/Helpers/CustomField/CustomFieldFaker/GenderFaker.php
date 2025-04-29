<?php

namespace Wideti\DomainBundle\Helpers\CustomField\CustomFieldFaker;

use Faker\Factory;

class GenderFaker implements CustomFieldFakerHelper
{
    public function generate($locale = 'pt_BR', $params = null)
    {
        $faker = Factory::create($locale);
        return $faker->randomElement(['Masculino', 'Feminino']);
    }
}
