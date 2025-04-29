<?php

namespace Wideti\DomainBundle\Tests\Service\Segmentation;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Document\Repository\SegmentationRepository;
use Wideti\DomainBundle\Document\Segmentation\Segmentation;
use Wideti\DomainBundle\Exception\InvalidDocumentException;
use Wideti\DomainBundle\Service\Segmentation\CreateSegmentationServiceImp;
use Wideti\DomainBundle\Service\Segmentation\EditSegmentationServiceImp;
use Wideti\DomainBundle\Service\Segmentation\Filter\Filter;

class EditSegmentationServiceTest extends WebTestCase
{
    /**
     * @throws DocumentNotFoundException
     * @throws InvalidDocumentException
     */
    public function testMustEditSegmentationSuccess()
    {
        $mockSegmentation = new Segmentation();
        $mockSegmentation->setStatus(Segmentation::ACTIVE);
        $mockSegmentation->setTitle('Segmentação de visitantes cadastrados em Janeiro');
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

        $mockSegmentationRepository = $this
            ->getMockBuilder(SegmentationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSegmentationRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->will($this->returnValue($mockSegmentation));
        $mockSegmentationRepository
            ->expects($this->once())
            ->method('save')
            ->will($this->returnValue($mockSegmentation));

        $service = new EditSegmentationServiceImp($mockSegmentationRepository);
        $result = $service->edit('5b6833ed066dd830008b4567', $mockSegmentation);

        $this->assertEquals(Segmentation::ACTIVE, $result->status);
        $this->assertEquals('Segmentação de visitantes cadastrados em Janeiro', $result->title);
    }

    /**
     * @throws DocumentNotFoundException
     * @throws InvalidDocumentException
     */
    public function testMustNotEditSegmentationDocumentNotFound()
    {
        $mockSegmentation = new Segmentation();
        $mockSegmentation->setStatus(Segmentation::ACTIVE);
        $mockSegmentation->setTitle('Segmentação de visitantes cadastrados em Janeiro');
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

        $mockSegmentationRepository = $this
            ->getMockBuilder(SegmentationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSegmentationRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->will($this->returnValue(null));
        $mockSegmentationRepository
            ->expects($this->never())
            ->method('save')
            ->will($this->returnValue($mockSegmentation));

        $this->setExpectedException(DocumentNotFoundException::class);

        $service = new EditSegmentationServiceImp($mockSegmentationRepository);
        $service->edit('123', $mockSegmentation);
    }

    /**
     * @throws InvalidDocumentException
     * @throws DocumentNotFoundException
     */
    public function testMustNotEditSegmentationTitleMissing()
    {
        $mockSegmentation = new Segmentation();
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

        $mockSegmentationRepository = $this
            ->getMockBuilder(SegmentationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSegmentationRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->will($this->returnValue($mockSegmentation));
        $mockSegmentationRepository
            ->expects($this->never())
            ->method('save')
            ->will($this->returnValue($mockSegmentation));

        $this->setExpectedException(InvalidDocumentException::class);

        $service = new EditSegmentationServiceImp($mockSegmentationRepository);
        $service->edit('5b6833ed066dd830008b4567', $mockSegmentation);
    }

    /**
     * @throws InvalidDocumentException
     * @throws DocumentNotFoundException
     */
    public function testMustNotEditSegmentationFilterMissing()
    {
        $mockSegmentation = new Segmentation();
        $mockSegmentation->setStatus(Segmentation::ACTIVE);
        $mockSegmentation->setTitle('Segmentação de visitantes cadastrados em Janeiro');

        $mockSegmentationRepository = $this
            ->getMockBuilder(SegmentationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSegmentationRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->will($this->returnValue($mockSegmentation));
        $mockSegmentationRepository
            ->expects($this->never())
            ->method('save')
            ->will($this->returnValue($mockSegmentation));

        $this->setExpectedException(InvalidDocumentException::class);

        $service = new EditSegmentationServiceImp($mockSegmentationRepository);
        $service->edit('5b6833ed066dd830008b4567', $mockSegmentation);
    }
}
