<?php

namespace Wideti\DomainBundle\Service\WSpotFaker;

use Wideti\DomainBundle\Entity\Client;

interface WSpotFakerServiceInterface
{
    public function execute(Client $client = null, $action);
}
