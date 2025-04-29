<?php

namespace Wideti\DomainBundle\DataFixtures\ORM\Test;

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
            /**
             * loading general fixtures
             */
            __DIR__.'/../Fixtures/Default/contracts.yml',
            __DIR__.'/../Fixtures/Default/roles.yml',
            __DIR__.'/../Fixtures/Default/plan.yml',
            __DIR__.'/../Fixtures/Default/segments.yml',
            __DIR__.'/../Fixtures/Default/reserved_domains.yml',
            __DIR__.'/../Fixtures/Default/vendor.yml',
            __DIR__.'/../Fixtures/Default/sms_gateway.yml',
            __DIR__.'/../Fixtures/Default/legal_kinds.yml',
            __DIR__.'/../Fixtures/Default/extra_config_type.yml',

            /**
             * loading sample Fixtures
             */
            __DIR__.'/Fixtures/SampleCustomFieldTemplate.yml',
            __DIR__.'/Fixtures/SampleModule.yml',
            __DIR__.'/Fixtures/SampleClients.yml',
            __DIR__.'/Fixtures/SampleModuleConfiguration.yml',
            __DIR__.'/Fixtures/SampleModuleConfigurationValue.yml',
            __DIR__.'/Fixtures/SampleUsers.yml',
            __DIR__.'/Fixtures/SampleGuests.yml',
            __DIR__.'/Fixtures/SampleTemplates.yml',
            __DIR__.'/Fixtures/SampleAccessPointsGroups.yml',
            __DIR__.'/Fixtures/SampleAccessPoints.yml',
            __DIR__.'/Fixtures/SampleCities.yml',
            __DIR__.'/Fixtures/SampleCountry.yml',
            __DIR__.'/Fixtures/SampleZone.yml',
            __DIR__.'/Fixtures/SampleTimezone.yml',
            __DIR__.'/Fixtures/SampleConfiguration.yml',
            __DIR__.'/Fixtures/SampleClientConfiguration.yml',
            __DIR__.'/Fixtures/SampleClientsLegalBase.yml',
            __DIR__.'/Fixtures/SampleWhiteLabel.yml',
            __DIR__.'/Fixtures/SampleApiWSpot.yml',
            __DIR__.'/Fixtures/SampleApiWSpotRoles.yml',
            __DIR__.'/Fixtures/SampleApiWSpotContracts.yml',
            __DIR__.'/Fixtures/SampleApiWSpotResources.yml',
            __DIR__.'/Fixtures/SampleAccessCodeSettings.yml'
        ];
    }
}
