<?php

namespace Wideti\DomainBundle\Tests\Service\WSpotFaker;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Document\Repository\GuestRepository;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Repository\AccessPointsRepository;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\RadacctRepository;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;
use Wideti\DomainBundle\Service\WSpotFaker\AccountingFaker;

class AccountingFakerTest extends WebTestCase
{
    public function testCreateAccountingSuccess()
    {
        $mockGuestRepository = $this
            ->getMockBuilder(GuestRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestRepository
            ->expects($this->any())
            ->method('findAll')
            ->willReturn([]);

        $mockAccessPointsRepository = $this
            ->getMockBuilder(AccessPointsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccessPointsRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn([]);

        $mockElasticSearch = $this
            ->getMockBuilder(ElasticSearch::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockElasticSearch
            ->expects($this->any())
            ->method('index')
            ->willReturn(true);

        $mockRadacctRepository = $this
            ->getMockBuilder(RadacctRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $client = Client::createClientWithId(13);

        $service = new AccountingFaker(
            $mockGuestRepository,
            $mockAccessPointsRepository,
            $mockElasticSearch,
            $mockRadacctRepository
        );
        $response = $service->create($client);
        $this->assertTrue($response);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateAccountingWithoutClient()
    {
        $mockGuestRepository = $this
            ->getMockBuilder(GuestRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestRepository
            ->expects($this->any())
            ->method('findAll')
            ->willReturn([]);

        $mockAccessPointsRepository = $this
            ->getMockBuilder(AccessPointsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccessPointsRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn([]);

        $mockElasticSearch = $this
            ->getMockBuilder(ElasticSearch::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockElasticSearch
            ->expects($this->any())
            ->method('index')
            ->willReturn(true);

        $mockRadacctRepository = $this
            ->getMockBuilder(RadacctRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $service = new AccountingFaker(
            $mockGuestRepository,
            $mockAccessPointsRepository,
            $mockElasticSearch,
            $mockRadacctRepository
        );

        $service->create();
    }

    public function testClearAccountingFakerSuccess()
    {
        $mockGuestRepository = $this
            ->getMockBuilder(GuestRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestRepository
            ->expects($this->any())
            ->method('findAll')
            ->willReturn([]);

        $mockAccessPointsRepository = $this
            ->getMockBuilder(AccessPointsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccessPointsRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn([]);

        $mockElasticSearch = $this
            ->getMockBuilder(ElasticSearch::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockElasticSearch
            ->expects($this->any())
            ->method('index')
            ->willReturn(true);

        $mockRadacctRepository = $this
            ->getMockBuilder(RadacctRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRadacctRepository
            ->expects($this->once())
            ->method('getAllIndexes')
            ->willReturn([]);

        $client = Client::createClientWithId(13);

        $service = new AccountingFaker(
            $mockGuestRepository,
            $mockAccessPointsRepository,
            $mockElasticSearch,
            $mockRadacctRepository
        );

        $response = $service->clear($client);
        $this->assertTrue($response);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testClearAccountingFakerWithoutClient()
    {
        $mockGuestRepository = $this
            ->getMockBuilder(GuestRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestRepository
            ->expects($this->any())
            ->method('findAll')
            ->willReturn([]);

        $mockAccessPointsRepository = $this
            ->getMockBuilder(AccessPointsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccessPointsRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn([]);

        $mockElasticSearch = $this
            ->getMockBuilder(ElasticSearch::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockElasticSearch
            ->expects($this->any())
            ->method('index')
            ->willReturn(true);

        $mockRadacctRepository = $this
            ->getMockBuilder(RadacctRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRadacctRepository
            ->expects($this->never())
            ->method('getAllIndexes')
            ->willReturn([]);

        $service = new AccountingFaker(
            $mockGuestRepository,
            $mockAccessPointsRepository,
            $mockElasticSearch,
            $mockRadacctRepository
        );

        $service->clear();
    }
}
