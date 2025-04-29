<?php

namespace Wideti\DomainBundle\Helpers\CustomField\CustomFieldFaker;

use Faker\Factory;

class DefaultFaker implements CustomFieldFakerHelper
{
    public function generate($locale = 'pt_BR', $params = null)
    {
        $faker = Factory::create($locale);
        return ucfirst($faker->words(1)[0]);
    }
}
