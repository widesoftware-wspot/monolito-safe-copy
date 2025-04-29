<?php

namespace Wideti\DomainBundle\Helpers;

use Faker\Factory;

class AccessDataHelper
{
    public static function randomAccessData($accessDate = null)
    {
        if (!$accessDate) {
            $accessDate = new \MongoDate();
        }

        $data = [
            [
                'macAddress'    => self::randomMacAddress(),
                'os'            => 'Android',
                'platform'      => 'Mobile',
                'accessDate'    => $accessDate
            ],
            [
                'macAddress'    => self::randomMacAddress(),
                'os'            => 'iOS',
                'platform'      => 'Mobile',
                'accessDate'    => $accessDate
            ],
            [
                'macAddress'    => self::randomMacAddress(),
                'os'            => 'Linux',
                'platform'      => 'PC',
                'accessDate'    => $accessDate
            ],
            [
                'macAddress'    => self::randomMacAddress(),
                'os'            => 'Windows',
                'platform'      => 'PC',
                'accessDate'    => $accessDate
            ],
            [
                'macAddress'    => self::randomMacAddress(),
                'os'            => 'Mac OSX',
                'platform'      => 'PC',
                'accessDate'    => $accessDate
            ],
        ];

        $accessData = array_rand($data, 1);

        return $data[$accessData];
    }

    public static function randomMacAddress()
    {
        $faker = Factory::create('pt_BR');
        $macArray = [];
        for ($i=0; $i<20; $i++) {
            array_push($macArray, strtoupper(str_replace(':', '-', $faker->macAddress)));
        }

        $mac = array_rand($macArray, 1);

        return strtoupper(str_replace(':', '-', $macArray[$mac]));
    }
}
