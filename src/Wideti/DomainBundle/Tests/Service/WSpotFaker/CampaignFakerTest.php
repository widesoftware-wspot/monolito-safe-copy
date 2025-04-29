<?php

namespace Wideti\DomainBundle\Tests\Service\WSpotFaker;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Repository\AccessPointsRepository;
use Wideti\DomainBundle\Repository\CampaignHoursRepository;
use Wideti\DomainBundle\Repository\CampaignRepository;
use Wideti\DomainBundle\Repository\CampaignViewsRepository;
use Wideti\DomainBundle\Repository\ClientRepository;
use Wideti\DomainBundle\Service\WSpotFaker\CampaignFaker;

class CampaignFakerTest extends WebTestCase
{
    public function testCreateCampaignSuccess()
    {
        $mockEntityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityManager
            ->expects($this->any())
            ->method('persist')
            ->willReturn([]);
        $mockEntityManager
            ->expects($this->once())
            ->method('flush')
            ->willReturn([]);

        $mockFileUpload = $this
            ->getMockBuilder(FileUpload::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockFileUpload
            ->expects($this->any())
            ->method('copyFileBetweenFolders')
            ->willReturn(true);

        $mockClientRepository = $this
            ->getMockBuilder(ClientRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockClientRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn(new Client());

        $mockCampaignRepository = $this
            ->getMockBuilder(CampaignRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockCampaignHoursRepository = $this
            ->getMockBuilder(CampaignHoursRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockCampaignViewsRepository = $this
            ->getMockBuilder(CampaignViewsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $client = Client::createClientWithId(13);

        $service = new CampaignFaker(
            $mockEntityManager,
            $mockFileUpload,
            $mockClientRepository,
            $mockCampaignRepository,
            $mockCampaignHoursRepository,
            $mockCampaignViewsRepository
        );
        $response = $service->create($client);
        $this->assertTrue($response);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateCheckinWithoutClient()
    {
        $mockEntityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityManager
            ->expects($this->never())
            ->method('persist')
            ->willReturn([]);
        $mockEntityManager
            ->expects($this->never())
            ->method('flush')
            ->willReturn([]);

        $mockFileUpload = $this
            ->getMockBuilder(FileUpload::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockFileUpload
            ->expects($this->any())
            ->method('copyFileBetweenFolders')
            ->willReturn(true);

        $mockClientRepository = $this
            ->getMockBuilder(ClientRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockClientRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn(new Client());

        $mockCampaignRepository = $this
            ->getMockBuilder(CampaignRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockCampaignHoursRepository = $this
            ->getMockBuilder(CampaignHoursRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockCampaignViewsRepository = $this
            ->getMockBuilder(CampaignViewsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $service = new CampaignFaker(
            $mockEntityManager,
            $mockFileUpload,
            $mockClientRepository,
            $mockCampaignRepository,
            $mockCampaignHoursRepository,
            $mockCampaignViewsRepository
        );
        $service->create(null);
    }

    public function testClearCheckinFakerSuccess()
    {
        $mockEntityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockFileUpload = $this
            ->getMockBuilder(FileUpload::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockFileUpload
            ->expects($this->once())
            ->method('deleteAllFiles')
            ->willReturn(true);

        $mockClientRepository = $this
            ->getMockBuilder(ClientRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockCampaignRepository = $this
            ->getMockBuilder(CampaignRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCampaignRepository
            ->expects($this->once())
            ->method('deleteAllByClient')
            ->willReturn([]);

        $mockCampaignHoursRepository = $this
            ->getMockBuilder(CampaignHoursRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCampaignHoursRepository
            ->expects($this->once())
            ->method('deleteAllByClient')
            ->willReturn([]);

        $mockCampaignViewsRepository = $this
            ->getMockBuilder(CampaignViewsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCampaignViewsRepository
            ->expects($this->once())
            ->method('deleteAllByClient')
            ->willReturn([]);

        $client = Client::createClientWithId(13);

        $service = new CampaignFaker(
            $mockEntityManager,
            $mockFileUpload,
            $mockClientRepository,
            $mockCampaignRepository,
            $mockCampaignHoursRepository,
            $mockCampaignViewsRepository
        );
        $response = $service->clear($client);
        $this->assertTrue($response);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testClearCheckinFakerWithoutClient()
    {
        $mockEntityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockFileUpload = $this
            ->getMockBuilder(FileUpload::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockFileUpload
            ->expects($this->never())
            ->method('deleteAllFiles')
            ->willReturn(true);

        $mockClientRepository = $this
            ->getMockBuilder(ClientRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockCampaignRepository = $this
            ->getMockBuilder(CampaignRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCampaignRepository
            ->expects($this->never())
            ->method('deleteAllByClient')
            ->willReturn([]);

        $mockCampaignHoursRepository = $this
            ->getMockBuilder(CampaignHoursRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCampaignHoursRepository
            ->expects($this->never())
            ->method('deleteAllByClient')
            ->willReturn([]);

        $mockCampaignViewsRepository = $this
            ->getMockBuilder(CampaignViewsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCampaignViewsRepository
            ->expects($this->never())
            ->method('deleteAllByClient')
            ->willReturn([]);

        $service = new CampaignFaker(
            $mockEntityManager,
            $mockFileUpload,
            $mockClientRepository,
            $mockCampaignRepository,
            $mockCampaignHoursRepository,
            $mockCampaignViewsRepository
        );
        $service->clear(null);
    }
}
