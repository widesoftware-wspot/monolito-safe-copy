<?php

namespace Wideti\DomainBundle\Service\Segmentation;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Wideti\DomainBundle\Entity\Segmentation;
use Wideti\DomainBundle\Helpers\SegmentationHelper;
use Wideti\DomainBundle\Repository\SegmentationRepository;

class EditSegmentationServiceImp implements EditSegmentationService
{
    /**
     * @var SegmentationRepository
     */
    private $repository;

    /**
     * EditSegmentationServiceImp constructor.
     * @param SegmentationRepository $repository
     */
    public function __construct(SegmentationRepository $repository)
    {
        $this->repository = $repository;
    }

	/**
	 * @param $segmentationId
	 * @param Segmentation $segmentation
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
    public function edit(Segmentation $segmentation)
    {
        try {
            $object = SegmentationHelper::validate($segmentation);
        } catch (\Exception $e) {
            throw $e;
        }

        return $this->repository->save($object);
    }
}
