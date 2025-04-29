<?php

namespace Wideti\DomainBundle\Tests\Service\Segmentation\Equality\Visits;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Document\Repository\GuestRepository;
use Wideti\DomainBundle\Service\Segmentation\Equality\Visits\Range;
use Wideti\DomainBundle\Service\Segmentation\Filter\Filter;
use Wideti\DomainBundle\Service\Segmentation\Filter\FilterItem;

class VisitsRangeTest extends WebTestCase
{
    public function testMustReturnOneIdOnTypeAllMode()
    {
        $item = new FilterItem();
        $item->setIdentifier('visits');
        $item->setEquality(FilterItem::RANGE);
        $item->setType('date');
        $item->setValue('2018-07-01|2018-07-20');

        $filter = new Filter();
        $filter->setType(Filter::TYPE_ALL);
        $filter->setClient(1);
        $filter->addIds([1,2,3]);
        $filter->addItem($item);

        $guest = new Guest();
        $guest->setMysql(1);

        $mockGuestRepository = $this
            ->getMockBuilder(GuestRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestRepository
            ->expects($this->once())
            ->method('segmentFindByLastAcessRange')
            ->will($this->returnValue([$guest]));

        $service = new Range($mockGuestRepository);
        $result  = $service->search($filter, $item);

        $this->assertNotEmpty($result->getIds());
        $this->assertContains(1, $result->getIds());
    }

    public function testMustReturnManyIdsOnTypeAnyMode()
    {
        $item = new FilterItem();
        $item->setIdentifier('visits');
        $item->setEquality(FilterItem::RANGE);
        $item->setType('date');
        $item->setValue('2018-07-01|2018-07-20');

        $filter = new Filter();
        $filter->setType(Filter::TYPE_ALL);
        $filter->setClient(1);
        $filter->addIds([1,2,3]);
        $filter->addItem($item);

        $guest1 = new Guest();
        $guest1->setMysql(1);
        $guest2= new Guest();
        $guest2->setMysql(2);
        $guest3 = new Guest();
        $guest3->setMysql(3);

        $mockGuestRepository = $this
            ->getMockBuilder(GuestRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestRepository
            ->expects($this->once())
            ->method('segmentFindByLastAcessRange')
            ->will($this->returnValue([$guest1, $guest2, $guest3]));

        $service = new Range($mockGuestRepository);
        $result  = $service->search($filter, $item);

        $this->assertNotEmpty($result->getIds());
        $this->assertContains(3, $result->getIds());
    }


    public function testMustReturnIdsWithTypeAllAndWithoutIds()
    {
        $item = new FilterItem();
        $item->setIdentifier('visits');
        $item->setEquality(FilterItem::RANGE);
        $item->setType('date');
        $item->setValue('2018-07-01|2018-07-20');

        $filter = new Filter();
        $filter->setType(Filter::TYPE_ALL);
        $filter->setClient(1);
        $filter->addIds([1,2,3]);
        $filter->addItem($item);

        $mockGuestRepository = $this
            ->getMockBuilder(GuestRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestRepository
            ->expects($this->once())
            ->method('segmentFindByLastAcessRange')
            ->will($this->returnValue([]));

        $service = new Range($mockGuestRepository);
        $result  = $service->search($filter, $item);

        $this->assertEmpty($result->getIds());
    }
}
