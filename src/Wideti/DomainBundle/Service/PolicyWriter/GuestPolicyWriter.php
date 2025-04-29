<?php

namespace Wideti\DomainBundle\Service\PolicyWriter;

use Rhumsaa\Uuid\Uuid;
use Wideti\DomainBundle\Document\Group\Group;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\NasManager\Dto\RadiusPolicy\RadiusPolicyBuilder;
use Wideti\FrontendBundle\Factory\Nas;

class GuestPolicyWriter implements PolicyWriter
{
    /**
     * @param Nas $nas
     * @param Guest $guest
     * @param Client $client
     * @param RadiusPolicyBuilder $builder
     * @return void
     */
    public function write(Nas $nas, Guest $guest, Client $client, RadiusPolicyBuilder $builder)
    {
        $employee = ($guest->getGroup() == Group::GROUP_EMPLOYEE)
            ? true
            : false
        ;

        $builder->withGuestPolicy($guest->getMysql(), Uuid::uuid4()->toString(), $employee);
    }
}
