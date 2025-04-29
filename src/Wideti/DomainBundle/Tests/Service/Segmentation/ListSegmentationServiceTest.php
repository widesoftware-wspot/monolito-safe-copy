<?php

namespace Wideti\DomainBundle\Tests\Service\Segmentation;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Document\Repository\SegmentationRepository;
use Wideti\DomainBundle\Document\Segmentation\Segmentation;
use Wideti\DomainBundle\Service\Segmentation\Filter\Filter;
use Wideti\DomainBundle\Service\Segmentation\ListSegmentationServiceImp;

class ListSegmentationServiceTest extends WebTestCase
{
    public function testMustReturnAllSegmentations()
    {
        $mockSegmentations = $this->getSegmentationArray();

        $mockSegmentationRepository = $this
            ->getMockBuilder(SegmentationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSegmentationRepository
            ->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue($mockSegmentations));

        $service = new ListSegmentationServiceImp($mockSegmentationRepository);
        $result = $service->listAll();

        $this->assertInternalType('array', $result);
        $this->assertEquals(2, count($result));
    }

    public function testMustReturnEmptySegmentations()
    {
        $mockSegmentations = [];

        $mockSegmentationRepository = $this
            ->getMockBuilder(SegmentationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSegmentationRepository
            ->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue($mockSegmentations));

        $service = new ListSegmentationServiceImp($mockSegmentationRepository);
        $result = $service->listAll();

        $this->assertInternalType('array', $result);
        $this->assertEquals(0, count($result));
    }

    private function getSegmentationArray()
    {
        $mock1 = new Segmentation();
        $mock1->setId(new \MongoId());
        $mock1->setStatus(Segmentation::ACTIVE);
        $mock1->setTitle('Segmentação de visitantes cadastrados de Janeiro');
        $mock1->setFilter(json_encode([
            [
                'client' => 1,
                'type'   => Filter::TYPE_ALL,
                'items'  => [
                    'default' => [
                        'registrations' => [
                            'identifier' => 'registrations',
                            'equality'   => 'range',
                            'type'       => 'date',
                            'value'      => '2018-01-01|2018-02-01'
                        ]
                    ]
                ]
            ]
        ]));
        $mock1->setCreated(new \MongoDate());
        $mock1->setUpdated(new \MongoDate());

        $mock2 = new Segmentation();
        $mock2->setId(new \MongoId());
        $mock2->setStatus(Segmentation::ACTIVE);
        $mock2->setTitle('Segmentação de visitantes cadastrados de Fevereiro');
        $mock2->setFilter(json_encode([
            [
                'client' => 1,
                'type'   => Filter::TYPE_ALL,
                'items'  => [
                    'default' => [
                        'registrations' => [
                            'identifier' => 'registrations',
                            'equality'   => 'range',
                            'type'       => 'date',
                            'value'      => '2018-02-01|2018-03-01'
                        ]
                    ]
                ]
            ]
        ]));
        $mock2->setCreated(new \MongoDate());
        $mock2->setUpdated(new \MongoDate());

        return [
            $mock1, $mock2
        ];
    }
}