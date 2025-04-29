<?php

namespace Wideti\DomainBundle\DataFixtures\ORM\Production;

use Wideti\DomainBundle\Helpers\FixtureHelper;
use Wideti\DomainBundle\Helpers\UserPasswordEncodingHelper;
use Hautelook\AliceBundle\Doctrine\DataFixtures\AbstractLoader;

class ProductionFixtures extends AbstractLoader
{
    use FixtureHelper;
    use UserPasswordEncodingHelper;

    /**
     * {@inheritDoc}
     */
    protected function getFixtures()
    {
        return [
            /**
             * loading general fixtures
             */
            __DIR__.'/../Fixtures/Default/contracts.yml',
            __DIR__.'/../Fixtures/Default/segments.yml',
            __DIR__.'/../Fixtures/Default/client.yml',
            __DIR__.'/../Fixtures/Default/roles.yml',
            __DIR__.'/../Fixtures/Default/module.yml',
            __DIR__.'/../Fixtures/Default/configuration.yml',
            __DIR__.'/../Fixtures/Default/sms_gateway.yml',
            __DIR__.'/../Fixtures/Default/legal_kinds.yml',
            __DIR__.'/../Fixtures/Default/extra_config_type.yml',

            /**
             * loading start data
             */
            __DIR__.'/../Fixtures/Default/users.yml',
            __DIR__.'/../Fixtures/Default/access_points_groups.yml',
            __DIR__.'/../Fixtures/Default/templates.yml',
            __DIR__.'/../Fixtures/Default/plan.yml',
            __DIR__.'/../Fixtures/Default/vendor.yml',
            __DIR__.'/../Fixtures/Default/reserved_domains.yml',
            __DIR__.'/../Fixtures/Default/vendor.yml',
            __DIR__.'/../Fixtures/Default/white_label.yml'
        ];
    }
}
