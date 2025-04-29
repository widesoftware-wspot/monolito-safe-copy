<?php

namespace Wideti\DomainBundle\Service\Segmentation;

use Doctrine\ORM\EntityNotFoundException;
use Wideti\DomainBundle\Entity\Segmentation;
use Wideti\DomainBundle\Repository\SegmentationRepository;
use Wideti\DomainBundle\Service\Report\ReportService;
use Wideti\DomainBundle\Service\Segmentation\Dto\ExportDto;

class ExportSegmentationServiceImp implements ExportSegmentationService
{
    /**
     * @var SegmentationRepository
     */
    private $segmentationRepository;
    /**
     * @var ReportService
     */
    private $reportService;

    /**
     * DeleteSegmentationServiceImp constructor.
     * @param SegmentationRepository $segmentationRepository
     * @param ReportService $reportService
     */
    public function __construct(
        SegmentationRepository $segmentationRepository,
        ReportService $reportService
    ) {
        $this->segmentationRepository = $segmentationRepository;
        $this->reportService = $reportService;
    }

	/**
	 * @param ExportDto $exportDto
	 * @return bool
	 * @throws EntityNotFoundException
	 */
    public function requestingExport(ExportDto $exportDto)
    {
        /** @var Segmentation $segmentation */
        $segmentation = $this->segmentationRepository->findOneBy([
            'id' => $exportDto->getSegmentationId()
        ]);

        if (!$segmentation) {
            throw new EntityNotFoundException();
        }

        return $this->reportService->segmentationExportBatch($exportDto);
    }
}
