<?php

namespace Wideti\DomainBundle\Tests\Service\WSpotFaker;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Repository\AccessPointsGroupsRepository;
use Wideti\DomainBundle\Repository\ClientRepository;
use Wideti\DomainBundle\Service\AccessPointsGroups\AccessPointsGroupsService;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\WSpotFaker\AccessPointGroupFaker;

class AccessPointGroupFakerTest extends WebTestCase
{
    public function testCreateAccessPointGroupFakerSuccess()
    {
        $mockConfigurationService = $this
            ->getMockBuilder(ConfigurationService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockConfigurationService
            ->expects($this->once())
            ->method('createConfigurationToSpecificGroup')
            ->willReturn(true);

        $mockAccessPointsGroupsService = $this
            ->getMockBuilder(AccessPointsGroupsService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccessPointsGroupsService
            ->expects($this->once())
            ->method('create')
            ->willReturn(true);

        $mockClientRepository = $this
            ->getMockBuilder(ClientRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockClientRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(Client::createClientWithId(13));

        $mockAccessPointsGroupsRepository = $this
            ->getMockBuilder(AccessPointsGroupsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccessPointsGroupsRepository
            ->expects($this->never())
            ->method('clearByClient')
            ->willReturn(Client::createClientWithId(13));

        $service = new AccessPointGroupFaker(
            $mockConfigurationService,
            $mockAccessPointsGroupsService,
            $mockClientRepository
        );

        $client = Client::createClientWithId(13);

        $response = $service->create($client);
        $this->assertTrue($response);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateAccessPointGroupFakerClientNull()
    {
        $mockConfigurationService = $this
            ->getMockBuilder(ConfigurationService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockConfigurationService
            ->expects($this->never())
            ->method('createConfigurationToSpecificGroup')
            ->willReturn(true);

        $mockAccessPointsGroupsService = $this
            ->getMockBuilder(AccessPointsGroupsService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccessPointsGroupsService
            ->expects($this->never())
            ->method('create')
            ->willReturn(true);

        $mockClientRepository = $this
            ->getMockBuilder(ClientRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockClientRepository
            ->expects($this->never())
            ->method('findOneBy')
            ->willReturn(Client::createClientWithId(13));

        $service = new AccessPointGroupFaker(
            $mockConfigurationService,
            $mockAccessPointsGroupsService,
            $mockClientRepository
        );

        $service->create(null);
    }

    public function testClearAccessPointGroupFakerSuccess()
    {
        $mockConfigurationService = $this
            ->getMockBuilder(ConfigurationService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockConfigurationService
            ->expects($this->once())
            ->method('removeAllButDefaults')
            ->willReturn(true);

        $mockAccessPointsGroupsService = $this
            ->getMockBuilder(AccessPointsGroupsService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccessPointsGroupsService
            ->expects($this->once())
            ->method('clearByClient')
            ->willReturn(true);

        $mockClientRepository = $this
            ->getMockBuilder(ClientRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockClientRepository
            ->expects($this->never())
            ->method('findOneBy')
            ->willReturn(Client::createClientWithId(13));

        $service = new AccessPointGroupFaker(
            $mockConfigurationService,
            $mockAccessPointsGroupsService,
            $mockClientRepository
        );

        $client = Client::createClientWithId(13);

        $response = $service->clear($client);
        $this->assertTrue($response);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testClearAccessPointGroupFakerWithClientNull()
    {
        $mockConfigurationService = $this
            ->getMockBuilder(ConfigurationService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockConfigurationService
            ->expects($this->never())
            ->method('removeAllButDefaults')
            ->willReturn(true);

        $mockAccessPointsGroupsService = $this
            ->getMockBuilder(AccessPointsGroupsService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccessPointsGroupsService
            ->expects($this->never())
            ->method('clearByClient')
            ->willReturn(true);

        $mockClientRepository = $this
            ->getMockBuilder(ClientRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockClientRepository
            ->expects($this->never())
            ->method('findOneBy')
            ->willReturn(Client::createClientWithId(13));

        $service = new AccessPointGroupFaker(
            $mockConfigurationService,
            $mockAccessPointsGroupsService,
            $mockClientRepository
        );

        $service->clear(null);
    }
}
