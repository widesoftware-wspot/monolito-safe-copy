<?php

namespace Wideti\DomainBundle\Tests\Service\WSpotFaker;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Repository\AccessPointsGroupsRepository;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointsService;
use Wideti\DomainBundle\Service\WSpotFaker\AccessPointFaker;

class AccessPointFakerTest extends WebTestCase
{
    public function testCreateAccessPointSuccess()
    {
        $mockAccessPointService = $this
            ->getMockBuilder(AccessPointsService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccessPointService
            ->expects($this->any())
            ->method('create')
            ->willReturn(true);

        $mockAccessPointGroupRepository = $this
            ->getMockBuilder(AccessPointsGroupsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccessPointGroupRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $client = Client::createClientWithId(13);

        $service = new AccessPointFaker($mockAccessPointService, $mockAccessPointGroupRepository);
        $response = $service->create($client);
        $this->assertTrue($response);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateAccessPointWithoutClient()
    {
        $mockAccessPointService = $this
            ->getMockBuilder(AccessPointsService::class)
            ->disableArgumentCloning()
            ->getMock();
        $mockAccessPointService
            ->expects($this->never())
            ->method('create')
            ->willReturn(true);

        $mockAccessPointGroupRepository = $this
            ->getMockBuilder(AccessPointsGroupsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccessPointGroupRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $service = new AccessPointFaker($mockAccessPointService, $mockAccessPointGroupRepository);
        $service->create(null);
    }

    public function testClearAccessPointFakerSuccess()
    {
        $mockAccessPointService = $this
            ->getMockBuilder(AccessPointsService::class)
            ->disableArgumentCloning()
            ->getMock();
        $mockAccessPointService
            ->expects($this->once())
            ->method('clearByClient')
            ->willReturn(true);

        $mockAccessPointGroupRepository = $this
            ->getMockBuilder(AccessPointsGroupsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccessPointGroupRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $client = Client::createClientWithId(13);

        $service = new AccessPointFaker($mockAccessPointService, $mockAccessPointGroupRepository);
        $response = $service->clear($client);
        $this->assertTrue($response);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testClearAccessPointFakerWithoutClient()
    {
        $mockAccessPointService = $this
            ->getMockBuilder(AccessPointsService::class)
            ->disableArgumentCloning()
            ->getMock();
        $mockAccessPointService
            ->expects($this->never())
            ->method('clearByClient')
            ->willReturn(true);

        $mockAccessPointGroupRepository = $this
            ->getMockBuilder(AccessPointsGroupsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccessPointGroupRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $service = new AccessPointFaker($mockAccessPointService, $mockAccessPointGroupRepository);
        $service->clear(null);
    }
}
