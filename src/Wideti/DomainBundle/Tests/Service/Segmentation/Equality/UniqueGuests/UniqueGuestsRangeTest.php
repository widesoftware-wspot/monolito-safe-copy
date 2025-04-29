<?php

namespace Wideti\DomainBundle\Tests\Service\Segmentation\Equality\Name;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\RadacctRepository;
use Wideti\DomainBundle\Service\Segmentation\Equality\UniqueGuests\Range;
use Wideti\DomainBundle\Service\Segmentation\Filter\Filter;
use Wideti\DomainBundle\Service\Segmentation\Filter\FilterItem;

class UniqueGuestsRangeTest extends WebTestCase
{
    public function testMustReturnOneIdOnTypeAllMode()
    {
        $item = new FilterItem();
        $item->setIdentifier('uniqueguests');
        $item->setEquality(FilterItem::RANGE);
        $item->setType('date');
        $item->setValue('2018-07-01|2018-07-20');

        $filter = new Filter();
        $filter->setType(Filter::TYPE_ALL);
        $filter->setClient(1);
        $filter->addIds([1,2,3]);
        $filter->addItem($item);

        $mockRadacctRepository = $this
            ->getMockBuilder(RadacctRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRadacctRepository
            ->expects($this->once())
            ->method('recurringOrUniqueGuestsIds')
            ->will($this->returnValue([1]));

        $service = new Range($mockRadacctRepository);
        $result  = $service->search($filter, $item);

        $this->assertNotEmpty($result->getIds());
        $this->assertContains(1, $result->getIds());
    }

    public function testMustReturnManyIdsOnTypeAnyMode()
    {
        $item = new FilterItem();
        $item->setIdentifier('uniqueguests');
        $item->setEquality(FilterItem::RANGE);
        $item->setType('date');
        $item->setValue('2018-07-01|2018-07-20');

        $filter = new Filter();
        $filter->setType(Filter::TYPE_ALL);
        $filter->setClient(1);
        $filter->addIds([1,2,3]);
        $filter->addItem($item);

        $mockRadacctRepository = $this
            ->getMockBuilder(RadacctRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRadacctRepository
            ->expects($this->once())
            ->method('recurringOrUniqueGuestsIds')
            ->will($this->returnValue([1, 2, 3]));

        $service = new Range($mockRadacctRepository);
        $result  = $service->search($filter, $item);

        $this->assertNotEmpty($result->getIds());
        $this->assertContains(3, $result->getIds());
    }

    public function testMustReturnIdsWithTypeAllAndWithoutIds()
    {
        $item = new FilterItem();
        $item->setIdentifier('uniqueguests');
        $item->setEquality(FilterItem::RANGE);
        $item->setType('date');
        $item->setValue('2018-07-01|2018-07-20');

        $filter = new Filter();
        $filter->setType(Filter::TYPE_ALL);
        $filter->setClient(1);
        $filter->addIds([1,2,3]);
        $filter->addItem($item);

        $mockRadacctRepository = $this
            ->getMockBuilder(RadacctRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRadacctRepository
            ->expects($this->once())
            ->method('recurringOrUniqueGuestsIds')
            ->will($this->returnValue([]));

        $service = new Range($mockRadacctRepository);
        $result  = $service->search($filter, $item);

        $this->assertEmpty($result->getIds());
    }
}
