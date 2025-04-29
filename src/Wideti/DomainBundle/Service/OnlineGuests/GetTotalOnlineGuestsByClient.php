<?php

namespace Wideti\DomainBundle\Service\OnlineGuests;

use Wideti\DomainBundle\Entity\Client;

interface GetTotalOnlineGuestsByClient
{
    public function get(Client $client);
}
