<?php

namespace Wideti\DomainBundle\Tests\Service\Segmentation;

use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Document\Repository\GuestRepository;
use Wideti\DomainBundle\Service\Segmentation\Equality\EqualityFactoryImp;
use Wideti\DomainBundle\Service\Segmentation\Equality\Registrations\Range;
use Wideti\DomainBundle\Service\Segmentation\Filter\Dto\FilterDto;
use Wideti\DomainBundle\Service\Segmentation\Filter\Dto\FilterItemDto;
use Wideti\DomainBundle\Service\Segmentation\Filter\Filter;
use Wideti\DomainBundle\Service\Segmentation\Filter\FilterItem;
use Wideti\DomainBundle\Service\Segmentation\Resolver\FilterResolver;

class FilterNameResolverTest extends WebTestCase
{
    public function testMustReturnFilterProcessed()
    {
        $filterItem = new FilterItemDto();
        $filterItem->setIdentifier('registrations');
        $filterItem->setEquality(FilterItem::RANGE);
        $filterItem->setType('date');
        $filterItem->setValue('2018-07-01|2018-07-20');

        $filter = new FilterDto();
        $filter->setType(Filter::TYPE_ALL);
        $filter->addItem($filterItem);

        $guest = new Guest();
        $guest->setMysql(1);

        $mockGuestRepository = $this
            ->getMockBuilder(GuestRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestRepository
            ->expects($this->once())
            ->method('segmentFindByCreatedRange')
            ->will($this->returnValue([$guest]));

        $mockEqualityFactory = $this
            ->getMockBuilder(EqualityFactoryImp::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockEqualityFactory
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue(new Range($mockGuestRepository)));

        $loggerMock = new Logger('foo');
        $service = new FilterResolver($mockEqualityFactory, $loggerMock);
        $result = $service->resolve($filter);

        $this->assertEquals(Filter::TYPE_ALL, $result->getType());
        $this->assertNotEmpty($result->getIds());
    }

    /**
     * @expectedException ServiceNotFoundException
     */
    public function testMustReturnServiceNotFoundException()
    {
        $filterItem = new FilterItemDto();
        $filterItem->setIdentifier('registrations');
        $filterItem->setEquality(FilterItem::RANGE);
        $filterItem->setType('date');
        $filterItem->setValue('2018-07-01|2018-07-20');

        $filter = new FilterDto();
        $filter->setType(Filter::TYPE_ALL);
        $filter->addItem($filterItem);

        $guest = new Guest();
        $guest->setMysql(1);

        $mockGuestRepository = $this
            ->getMockBuilder(GuestRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestRepository
            ->expects($this->never())
            ->method('segmentFindByCreatedRange')
            ->will($this->returnValue([$guest]));

        $mockEqualityFactory = $this
            ->getMockBuilder(EqualityFactoryImp::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockEqualityFactory
            ->expects($this->once())
            ->method('get')
            ->will($this->throwException(new ServiceNotFoundException('segmentation.datasource.registrations.range')));

        $this->setExpectedException(ServiceNotFoundException::class);

        $loggerMock = new Logger('foo');
        $service = new FilterResolver($mockEqualityFactory, $loggerMock);
        $service->resolve($filter);
    }
}
