<?php

namespace Wideti\ApiBundle\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

abstract class IntegrationTestCase extends WebTestCase
{
    protected $token;
    protected $baseHost;
    /** @var Client */
    protected $httpClient;
    protected $container;

    public function setUp()
    {
        $this->container = static::createClient()->getContainer();
        $this->token = $this->container->getParameter('test_api_token');
        $this->baseHost = $this->container->getParameter('test_api_url');

        $this->httpClient = $client = static::createClient([], [
            "HTTP_HOST" => $this->baseHost,
            "HTTP_x-token" => $this->token
        ]);
    }

    public function tearDown()
    {
//        $this->client->reload();
        $this->httpClient->restart();
    }
}
