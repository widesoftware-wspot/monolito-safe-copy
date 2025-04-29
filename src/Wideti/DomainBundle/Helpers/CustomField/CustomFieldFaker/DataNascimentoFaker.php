<?php

namespace Wideti\DomainBundle\Helpers\CustomField\CustomFieldFaker;

use Faker\Factory;

class DataNascimentoFaker implements CustomFieldFakerHelper
{
    public function generate($locale = 'pt_BR', $params = null)
    {
        $faker = Factory::create($locale);
        $baseDate = $faker->dateTimeThisCentury;
        return new \MongoDate(strtotime($baseDate->format('Y-m-d H:i:s')));
    }
}
