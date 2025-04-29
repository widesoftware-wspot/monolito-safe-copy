<?php

namespace Wideti\DomainBundle\Service\WSpotFaker;

use Wideti\DomainBundle\Entity\Client;

interface WSpotFaker
{
    public function create(Client $client = null);
    public function clear(Client $client = null);
}
