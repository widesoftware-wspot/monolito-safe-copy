<?php

namespace Wideti\DomainBundle\Tests\Service\Segmentation;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Document\CustomFields\Field;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Document\Repository\GuestRepository;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsService;
use Wideti\DomainBundle\Service\Segmentation\Filter\Dto\FilterDto;
use Wideti\DomainBundle\Service\Segmentation\Filter\Dto\FilterItemDto;
use Wideti\DomainBundle\Service\Segmentation\Filter\Filter;
use Wideti\DomainBundle\Service\Segmentation\Filter\FilterItem;
use Wideti\DomainBundle\Service\Segmentation\Resolver\FilterResolver;
use Wideti\DomainBundle\Service\Segmentation\PreviewSegmentationServiceImp;

class PreviewSegmentationTest extends WebTestCase
{
    /**
     * @throws \Exception
     */
    public function testMustReturnPreviewWithData()
    {
        $filterItem = new FilterItem();
        $filterItem->setIdentifier('registrations');
        $filterItem->setEquality(FilterItem::RANGE);
        $filterItem->setType('date');
        $filterItem->setValue('2018-07-01|2018-07-20');

        $filter = new Filter();
        $filter->setType(Filter::TYPE_ALL);
        $ids = \range(1, 10);
        $filter->setIds($ids);
        $filter->addItem($filterItem);

        $mockFilterResolver = $this
            ->getMockBuilder(FilterResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockFilterResolver
            ->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue($filter));

        /**
         * ############################################################################################################
         */

        $guest = new Guest();
        $guest->setMysql(1);
        $guest->addProperty('email', 'user@user.com');

        $mockGuestRepository = $this
            ->getMockBuilder(GuestRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestRepository
            ->expects($this->once())
            ->method('findByIds')
            ->will($this->returnValue([$guest]));

        /**
         * ############################################################################################################
         */

        $customFields = new Field();
        $customFields->setIdentifier('email');

        $mockCustomFieldsService = $this
            ->getMockBuilder(CustomFieldsService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCustomFieldsService
            ->expects($this->once())
            ->method('getLoginField')
            ->will($this->returnValue([$customFields]));

        /**
         * ############################################################################################################
         */

        $filterItemDto = new FilterItemDto();
        $filterItemDto->setIdentifier('registrations');
        $filterItemDto->setEquality(FilterItem::RANGE);
        $filterItemDto->setType('date');
        $filterItemDto->setValue('2018-07-01|2018-07-20');

        $filterDto = new FilterDto();
        $filterDto->setType(Filter::TYPE_ALL);
        $filterDto->addItem($filterItemDto);

        $service = new PreviewSegmentationServiceImp($mockFilterResolver, $mockGuestRepository, $mockCustomFieldsService);
        $result = $service->preview($filterDto);

        $this->assertNotEmpty($result);
        $this->assertEquals(1, $result['count']);
        $this->assertEquals('user@user.com', $result['preview'][0]['field']);
    }

    /**
     * @throws \Exception
     */
    public function testMustReturnPreviewEmpty()
    {
        $filterItem = new FilterItem();
        $filterItem->setIdentifier('registrations');
        $filterItem->setEquality(FilterItem::RANGE);
        $filterItem->setType('date');
        $filterItem->setValue('2018-07-01|2018-07-20');

        $filter = new Filter();
        $filter->setType(Filter::TYPE_ALL);
        $filter->setIds([]);
        $filter->addItem($filterItem);

        $mockFilterResolver = $this
            ->getMockBuilder(FilterResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockFilterResolver
            ->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue($filter));

        /**
         * ############################################################################################################
         */

        $mockGuestRepository = $this
            ->getMockBuilder(GuestRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGuestRepository
            ->expects($this->once())
            ->method('findByIds')
            ->will($this->returnValue([]));

        /**
         * ############################################################################################################
         */

        $customFields = new Field();
        $customFields->setIdentifier('email');

        $mockCustomFieldsService = $this
            ->getMockBuilder(CustomFieldsService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCustomFieldsService
            ->expects($this->once())
            ->method('getLoginField')
            ->will($this->returnValue([$customFields]));

        /**
         * ############################################################################################################
         */

        $filterItemDto = new FilterItemDto();
        $filterItemDto->setIdentifier('registrations');
        $filterItemDto->setEquality(FilterItem::RANGE);
        $filterItemDto->setType('date');
        $filterItemDto->setValue('2018-07-01|2018-07-20');

        $filterDto = new FilterDto();
        $filterDto->setType(Filter::TYPE_ALL);
        $filterDto->addItem($filterItemDto);

        $service = new PreviewSegmentationServiceImp($mockFilterResolver, $mockGuestRepository, $mockCustomFieldsService);
        $result = $service->preview($filterDto);

        $this->assertNotEmpty($result);
        $this->assertEquals(0, $result['count']);
        $this->assertEmpty($result['preview']);
    }
}
