<?php

namespace Wideti\DomainBundle\Tests\Service\Segmentation;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wideti\DomainBundle\Document\Repository\SegmentationRepository;
use Wideti\DomainBundle\Document\Segmentation\Segmentation;
use Wideti\DomainBundle\Exception\InvalidDocumentException;
use Wideti\DomainBundle\Service\Report\ReportService;
use Wideti\DomainBundle\Service\Segmentation\CreateSegmentationServiceImp;
use Wideti\DomainBundle\Service\Segmentation\Dto\ExportDto;
use Wideti\DomainBundle\Service\Segmentation\EditSegmentationServiceImp;
use Wideti\DomainBundle\Service\Segmentation\ExportSegmentationServiceImp;
use Wideti\DomainBundle\Service\Segmentation\Filter\Filter;

class ExportSegmentationServiceTest extends WebTestCase
{
    /**
     * @throws DocumentNotFoundException
     */
    public function testMustRequestExportSegmentationSuccess()
    {
        $mockExportDto = new ExportDto();
        $mockExportDto->setClient(1);
        $mockExportDto->setSegmentationId('sa78d6sa7s4d56sa3');
        $mockExportDto->setRecipient('developers@widesoftware.com.br');

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

        $mockReportService = $this
            ->getMockBuilder(ReportService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockReportService
            ->expects($this->once())
            ->method('segmentationExportBatch')
            ->will($this->returnValue(true));

        $service = new ExportSegmentationServiceImp($mockSegmentationRepository, $mockReportService);
        $result = $service->requestingExport($mockExportDto);

        $this->assertTrue($result);
    }

    /**
     * @throws DocumentNotFoundException
     * @throws InvalidDocumentException
     */
    public function testMustNotEditSegmentationDocumentNotFound()
    {
        $mockExportDto = new ExportDto();
        $mockExportDto->setClient(1);
        $mockExportDto->setSegmentationId('sa78d6sa7s4d56sa3');
        $mockExportDto->setRecipient('developers@widesoftware.com.br');

        $mockSegmentationRepository = $this
            ->getMockBuilder(SegmentationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSegmentationRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->will($this->returnValue(null));

        $mockReportService = $this
            ->getMockBuilder(ReportService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockReportService
            ->expects($this->never())
            ->method('segmentationExportBatch')
            ->will($this->returnValue(true));

        $this->setExpectedException(DocumentNotFoundException::class);

        $service = new ExportSegmentationServiceImp($mockSegmentationRepository, $mockReportService);
        $service->requestingExport($mockExportDto);
    }
}
