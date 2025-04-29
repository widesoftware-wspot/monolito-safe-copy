<?php

namespace Wideti\DomainBundle\Tests\Service\WSpotFaker;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\WSpotFaker\WSpotFakerManager;
use Wideti\DomainBundle\Service\WSpotFaker\WSpotFakerService;

class WSpotFakerServiceTest extends WebTestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExecuteWithoutClient()
    {
        $service = $this->getWspotFakerService();
        $service->execute(null, 'create');
    }

    public function testExecuteWithIncorrectActionParam()
    {
        $client     = Client::createClientWithId(13);
        $service    = $this->getWspotFakerService();
        $response   = $service->execute($client, 'foo');
        $this->assertEquals($response, false);
    }

    private function getWspotFakerService()
    {
        return new WSpotFakerService(new WSpotFakerManager());
    }
}
