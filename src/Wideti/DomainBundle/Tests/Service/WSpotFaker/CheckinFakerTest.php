<?php

namespace Wideti\DomainBundle\Tests\Service\WSpotFaker;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Document\Repository\GuestRepository;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;
use Wideti\DomainBundle\Service\WSpotFaker\CheckinFaker;

class CheckinFakerTest extends WebTestCase
{
    public function testCreateCheckinSuccess()
    {
        $mockGuestRepository = $this
            ->getMockBuilder(GuestRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestRepository
            ->expects($this->any())
            ->method('findAll')
            ->willReturn([]);

        $mockElasticSearch = $this
            ->getMockBuilder(ElasticSearch::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockElasticSearch
            ->expects($this->any())
            ->method('index')
            ->willReturn(true);

        $client = Client::createClientWithId(13);

        $service = new CheckinFaker(
            $mockGuestRepository,
            $mockElasticSearch
        );
        $response = $service->create($client);
        $this->assertTrue($response);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateCheckinWithoutClient()
    {
        $mockGuestRepository = $this
            ->getMockBuilder(GuestRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestRepository
            ->expects($this->any())
            ->method('findAll')
            ->willReturn([]);

        $mockElasticSearch = $this
            ->getMockBuilder(ElasticSearch::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockElasticSearch
            ->expects($this->any())
            ->method('index')
            ->willReturn(true);

        $service = new CheckinFaker(
            $mockGuestRepository,
            $mockElasticSearch
        );

        $service->create();
    }

    public function testClearCheckinFakerSuccess()
    {
        $mockGuestRepository = $this
            ->getMockBuilder(GuestRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestRepository
            ->expects($this->any())
            ->method('findAll')
            ->willReturn([]);

        $mockElasticSearch = $this
            ->getMockBuilder(ElasticSearch::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockElasticSearch
            ->expects($this->any())
            ->method('bulk')
            ->willReturn(true);

        $client = Client::createClientWithId(13);

        $service = new CheckinFaker(
            $mockGuestRepository,
            $mockElasticSearch
        );

        $response = $service->clear($client);
        $this->assertTrue($response);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testClearCheckinFakerWithoutClient()
    {
        $mockGuestRepository = $this
            ->getMockBuilder(GuestRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestRepository
            ->expects($this->any())
            ->method('findAll')
            ->willReturn([]);

        $mockElasticSearch = $this
            ->getMockBuilder(ElasticSearch::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockElasticSearch
            ->expects($this->any())
            ->method('bulk')
            ->willReturn(true);

        $service = new CheckinFaker(
            $mockGuestRepository,
            $mockElasticSearch
        );

        $service->clear();
    }
}
