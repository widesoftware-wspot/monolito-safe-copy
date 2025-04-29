<?php

namespace Wideti\DomainBundle\Tests\Service\Segmentation;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Service\Segmentation\Filter\Filter;
use Wideti\DomainBundle\Service\Segmentation\Filter\FilterItem;

class FilterTest extends WebTestCase
{
    public function testMustReturnHasItemExists()
    {
        $item1 = new FilterItem();
        $item1->setIdentifier('name');
        $item1->setEquality(FilterItem::IS);
        $item1->setType('text');
        $item1->setValue('joao');

        $item2 = new FilterItem();
        $item2->setIdentifier('gender');
        $item2->setEquality(FilterItem::IS);
        $item2->setType('text');
        $item2->setValue('male');

        $filter = new Filter();
        $filter->setType('all');
        $filter->setIds([]);
        $filter->addItem($item1);
        $filter->addItem($item2);

        $this->assertTrue($filter->hasItem('gender'));
    }

    public function testMustReturnHasItemNotExists()
    {
        $item1 = new FilterItem();
        $item1->setIdentifier('name');
        $item1->setEquality(FilterItem::IS);
        $item1->setType('text');
        $item1->setValue('joao');

        $item2 = new FilterItem();
        $item2->setIdentifier('gender');
        $item2->setEquality(FilterItem::IS_NOT);
        $item2->setType('text');
        $item2->setValue('female');

        $filter = new Filter();
        $filter->setType('all');
        $filter->setIds([]);
        $filter->addItem($item1);
        $filter->addItem($item2);

        $this->assertFalse($filter->hasItem('foo'));
    }
}
