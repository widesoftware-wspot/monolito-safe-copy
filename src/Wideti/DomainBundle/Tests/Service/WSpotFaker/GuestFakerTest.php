<?php

namespace Wideti\DomainBundle\Tests\Service\WSpotFaker;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Document\Repository\GuestRepository;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Repository\AccessPointsRepository;
use Wideti\DomainBundle\Repository\SmsHistoricRepository;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsService;
use Wideti\DomainBundle\Service\Guest\GuestService;
use Wideti\DomainBundle\Service\WSpotFaker\GuestFaker;

class GuestFakerTest extends WebTestCase
{
    public function testCreateGuestSuccess()
    {
        $mockGuestRepository = $this
            ->getMockBuilder(GuestRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestRepository
            ->expects($this->any())
            ->method('findAll')
            ->willReturn([]);

        $mockSmsHistoricRepository = $this
            ->getMockBuilder(SmsHistoricRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSmsHistoricRepository
            ->expects($this->never())
            ->method('deleteByClient')
            ->willReturn([]);

        $mockAccessPointsRepository = $this
            ->getMockBuilder(AccessPointsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccessPointsRepository
            ->expects($this->any())
            ->method('findBy')
            ->willReturn([null]);

        $mockCustomFieldsService = $this
            ->getMockBuilder(CustomFieldsService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCustomFieldsService
            ->expects($this->once())
            ->method('getCustomFields')
            ->willReturn([]);

        $mockGuestService = $this
            ->getMockBuilder(GuestService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestService
            ->expects($this->any())
            ->method('createByAdmin')
            ->willReturn(true);

        $client = Client::createClientWithId(13);

        $service = new GuestFaker(
            $mockGuestService,
            $mockGuestRepository,
            $mockSmsHistoricRepository,
            $mockAccessPointsRepository,
            $mockCustomFieldsService
        );
        $response = $service->create($client);
        $this->assertTrue($response);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateGuestWithoutClient()
    {
        $mockGuestRepository = $this
            ->getMockBuilder(GuestRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestRepository
            ->expects($this->any())
            ->method('findAll')
            ->willReturn([]);

        $mockSmsHistoricRepository = $this
            ->getMockBuilder(SmsHistoricRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSmsHistoricRepository
            ->expects($this->never())
            ->method('deleteByClient')
            ->willReturn([]);

        $mockAccessPointsRepository = $this
            ->getMockBuilder(AccessPointsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccessPointsRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn([]);

        $mockCustomFieldsService = $this
            ->getMockBuilder(CustomFieldsService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCustomFieldsService
            ->expects($this->never())
            ->method('getCustomFields')
            ->willReturn([]);

        $mockGuestService = $this
            ->getMockBuilder(GuestService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestService
            ->expects($this->any())
            ->method('createByAdmin')
            ->willReturn(true);

        $service = new GuestFaker(
            $mockGuestService,
            $mockGuestRepository,
            $mockSmsHistoricRepository,
            $mockAccessPointsRepository,
            $mockCustomFieldsService
        );
        $service->create(null);
    }

    public function testClearGuestFakerSuccess()
    {
        $mockGuestRepository = $this
            ->getMockBuilder(GuestRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestRepository
            ->expects($this->any())
            ->method('findAll')
            ->willReturn([]);

        $mockSmsHistoricRepository = $this
            ->getMockBuilder(SmsHistoricRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSmsHistoricRepository
            ->expects($this->any())
            ->method('deleteByClient')
            ->willReturn(true);

        $mockAccessPointsRepository = $this
            ->getMockBuilder(AccessPointsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccessPointsRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn([]);

        $mockCustomFieldsService = $this
            ->getMockBuilder(CustomFieldsService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCustomFieldsService
            ->expects($this->never())
            ->method('getCustomFields')
            ->willReturn([]);

        $mockGuestService = $this
            ->getMockBuilder(GuestService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestService
            ->expects($this->any())
            ->method('createByAdmin')
            ->willReturn(true);

        $client = Client::createClientWithId(13);

        $service = new GuestFaker(
            $mockGuestService,
            $mockGuestRepository,
            $mockSmsHistoricRepository,
            $mockAccessPointsRepository,
            $mockCustomFieldsService
        );
        $response = $service->clear($client);
        $this->assertTrue($response);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testClearGuestFakerWithoutClient()
    {
        $mockGuestRepository = $this
            ->getMockBuilder(GuestRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestRepository
            ->expects($this->any())
            ->method('findAll')
            ->willReturn([]);

        $mockSmsHistoricRepository = $this
            ->getMockBuilder(SmsHistoricRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSmsHistoricRepository
            ->expects($this->any())
            ->method('deleteByClient')
            ->willReturn([]);

        $mockAccessPointsRepository = $this
            ->getMockBuilder(AccessPointsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccessPointsRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn([]);

        $mockCustomFieldsService = $this
            ->getMockBuilder(CustomFieldsService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCustomFieldsService
            ->expects($this->never())
            ->method('getCustomFields')
            ->willReturn([]);

        $mockGuestService = $this
            ->getMockBuilder(GuestService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestService
            ->expects($this->any())
            ->method('createByAdmin')
            ->willReturn(true);

        $service = new GuestFaker(
            $mockGuestService,
            $mockGuestRepository,
            $mockSmsHistoricRepository,
            $mockAccessPointsRepository,
            $mockCustomFieldsService
        );
        $service->clear(null);
    }
}
