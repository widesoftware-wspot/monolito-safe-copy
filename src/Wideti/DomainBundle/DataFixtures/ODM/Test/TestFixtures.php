<?php

namespace Wideti\DomainBundle\DataFixtures\ODM\Test;

use Wideti\DomainBundle\Helpers\FixtureHelper;
use Wideti\DomainBundle\Helpers\UserPasswordEncodingHelper;
use Hautelook\AliceBundle\Doctrine\DataFixtures\AbstractLoader;

class TestFixtures extends AbstractLoader
{
    use FixtureHelper;
    use UserPasswordEncodingHelper;

    /**
     * {@inheritDoc}
     */
    public function getFixtures()
    {
        return [
            __DIR__.'/Fixtures/SampleFields.yml',
            __DIR__.'/Fixtures/SampleGuestsGroupsConfigurationValue.yml',
            __DIR__.'/Fixtures/SampleGuestsGroupsConfiguration.yml',
            __DIR__.'/Fixtures/SampleGuestGroupAccessPoint.yml',
            __DIR__.'/Fixtures/SampleGuestGroupAccessPointGroup.yml',
            __DIR__.'/Fixtures/SampleGuestsGroups.yml',
            __DIR__.'/Fixtures/SampleGuests.yml'
        ];
    }
}
