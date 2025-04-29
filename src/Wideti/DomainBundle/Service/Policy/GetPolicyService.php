<?php

namespace Wideti\DomainBundle\Service\Policy;

use Wideti\DomainBundle\Entity\Client;

interface GetPolicyService
{
    public function getByGuestMacAddress(Client $client, $guestMacAddress);
}
