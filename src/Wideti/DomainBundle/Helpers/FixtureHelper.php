<?php

namespace Wideti\DomainBundle\Helpers;

trait FixtureHelper
{

    /**
     * @return mixed
     */
    public function randomPlaform()
    {
        $plataforms = array(
            'PC',
            'Mobile',
        );

        return $plataforms[array_rand($plataforms, 1)];
    }

    /**
     * @return mixed
     */
    public function randomOperatingSystem()
    {
        $operatingSystem = array(
            'Mac OSX',
            'Android',
            'iOS',
            'Windows XP',
            'Windows 7',
            'Windows Vista/Window',
            'Windows 8',
            'Ubuntu',
        );

        return $operatingSystem[array_rand($operatingSystem, 1)];
    }
}
