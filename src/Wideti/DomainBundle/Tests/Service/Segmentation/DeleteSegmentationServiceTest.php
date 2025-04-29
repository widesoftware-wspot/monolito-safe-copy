<?php

namespace Wideti\DomainBundle\Tests\Service\Segmentation;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Document\Repository\SegmentationRepository;
use Wideti\DomainBundle\Document\Segmentation\Segmentation;
use Wideti\DomainBundle\Service\Segmentation\DeleteSegmentationServiceImp;
use Wideti\DomainBundle\Service\Segmentation\Filter\Filter;

class DeleteSegmentationServiceTest extends WebTestCase
{
    /**
     * @throws DocumentNotFoundException
     */
    public function testMustDeleteSegmentationSuccess()
    {
        $mockSegmentation = new Segmentation();
        $mockSegmentation->setId('acb123');
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
            ->method('delete')
            ->will($this->returnValue(true));

        $service = new DeleteSegmentationServiceImp($mockSegmentationRepository);
        $result = $service->delete('abc123');
        $this->assertTrue($result);
    }

    /**
     * @throws DocumentNotFoundException
     */
    public function testMustDeleteSegmentationWithoutIdReturnException()
    {
        $mockSegmentationRepository = $this
            ->getMockBuilder(SegmentationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSegmentationRepository
            ->expects($this->never())
            ->method('findOneBy')
            ->will($this->returnValue(null));
        $mockSegmentationRepository
            ->expects($this->never())
            ->method('delete')
            ->will($this->returnValue(true));

        $this->setExpectedException(DocumentNotFoundException::class);

        $service = new DeleteSegmentationServiceImp($mockSegmentationRepository);
        $service->delete(null);
    }

    /**
     * @throws DocumentNotFoundException
     */
    public function testMustDeleteSegmentationNonExistentReturnException()
    {
        $mockSegmentation = new Segmentation();
        $mockSegmentation->setId('acb123');
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
            ->method('delete')
            ->will($this->returnValue(true));

        $this->setExpectedException(DocumentNotFoundException::class);

        $service = new DeleteSegmentationServiceImp($mockSegmentationRepository);
        $service->delete('abc123');
    }
}
