<?php

namespace Wideti\DomainBundle\Tests\Service\Segmentation;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Document\Repository\SegmentationRepository;
use Wideti\DomainBundle\Document\Segmentation\Segmentation;
use Wideti\DomainBundle\Exception\InvalidDocumentException;
use Wideti\DomainBundle\Service\Segmentation\CreateSegmentationServiceImp;
use Wideti\DomainBundle\Service\Segmentation\Filter\Filter;

class CreateSegmentationServiceTest extends WebTestCase
{
    public function testMustCreateSegmentationSuccess()
    {
        $mockSegmentation = new Segmentation();
        $mockSegmentation->setId(new \MongoId());
        $mockSegmentation->setStatus(Segmentation::ACTIVE);
        $mockSegmentation->setTitle('Segmentação de visitantes cadastrados');
        $mockSegmentation->setFilter(json_encode([
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
        $mockSegmentation->setCreated(new \MongoDate());
        $mockSegmentation->setUpdated(new \MongoDate());

        $mockSegmentationRepository = $this
            ->getMockBuilder(SegmentationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSegmentationRepository
            ->expects($this->once())
            ->method('save')
            ->will($this->returnValue($mockSegmentation));

        $service = new CreateSegmentationServiceImp($mockSegmentationRepository);
        $result = $service->create($mockSegmentation);

        $this->assertEquals(Segmentation::ACTIVE, $result->status);
        $this->assertEquals('Segmentação de visitantes cadastrados', $result->title);
    }

    /**
     * @throws InvalidDocumentException
     */
    public function testMustNotCreateSegmentationTitleMissing()
    {
        $mockSegmentation = new Segmentation();
        $mockSegmentation->setId(new \MongoId());
        $mockSegmentation->setStatus(Segmentation::ACTIVE);
        $mockSegmentation->setFilter(json_encode([
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
        $mockSegmentation->setCreated(new \MongoDate());
        $mockSegmentation->setUpdated(new \MongoDate());

        $mockSegmentationRepository = $this
            ->getMockBuilder(SegmentationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSegmentationRepository
            ->expects($this->never())
            ->method('save')
            ->will($this->returnValue($mockSegmentation));

        $this->setExpectedException(InvalidDocumentException::class);

        $service = new CreateSegmentationServiceImp($mockSegmentationRepository);
        $service->create($mockSegmentation);
    }

    /**
     * @throws InvalidDocumentException
     */
    public function testMustNotCreateSegmentationFilterMissing()
    {
        $mockSegmentation = new Segmentation();
        $mockSegmentation->setId(new \MongoId());
        $mockSegmentation->setStatus(Segmentation::ACTIVE);
        $mockSegmentation->setTitle('Segmentação de visitantes cadastrados');
        $mockSegmentation->setCreated(new \MongoDate());
        $mockSegmentation->setUpdated(new \MongoDate());

        $mockSegmentationRepository = $this
            ->getMockBuilder(SegmentationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSegmentationRepository
            ->expects($this->never())
            ->method('save')
            ->will($this->returnValue($mockSegmentation));

        $this->setExpectedException(InvalidDocumentException::class);

        $service = new CreateSegmentationServiceImp($mockSegmentationRepository);
        $service->create($mockSegmentation);
    }
}