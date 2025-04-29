<?php

namespace Wideti\DomainBundle\Service\Guzzle;

use GuzzleHttp\Client;

class GuzzleFactory
{
    public static function create()
    {
        return new Client();
    }
}
