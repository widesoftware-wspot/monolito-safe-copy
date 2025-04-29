<?php

namespace Wideti\DomainBundle\Service\Segmentation;

use Doctrine\ORM\EntityNotFoundException;
use Wideti\DomainBundle\Entity\Segmentation;
use Wideti\DomainBundle\Repository\SegmentationRepository;

class DeleteSegmentationServiceImp implements DeleteSegmentationService
{
    /**
     * @var SegmentationRepository
     */
    private $repository;

    /**
     * DeleteSegmentationServiceImp constructor.
     * @param SegmentationRepository $repository
     */
    public function __construct(SegmentationRepository $repository)
    {
        $this->repository = $repository;
    }

	/**
	 * @param Segmentation $segmentation
	 * @throws EntityNotFoundException
	 */
    public function delete(Segmentation $segmentation)
    {
        if (!$segmentation) {
            throw new EntityNotFoundException();
        }

        try {
            return $this->repository->delete($segmentation);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
