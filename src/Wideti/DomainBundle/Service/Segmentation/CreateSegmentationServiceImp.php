<?php

namespace Wideti\DomainBundle\Service\Segmentation;

use Wideti\DomainBundle\Entity\Segmentation;
use Wideti\DomainBundle\Exception\InvalidEntityException;
use Wideti\DomainBundle\Helpers\SegmentationHelper;
use Wideti\DomainBundle\Repository\SegmentationRepository;

class CreateSegmentationServiceImp implements CreateSegmentationService
{
    /**
     * @var SegmentationRepository
     */
    private $repository;

    /**
     * CreateSegmentationServiceImp constructor.
     * @param SegmentationRepository $repository
     */
    public function __construct(SegmentationRepository $repository)
    {
        $this->repository = $repository;
    }

	/**
	 * @param Segmentation $segmentation
	 * @throws InvalidEntityException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Wideti\DomainBundle\Exception\InvalidDocumentException
	 */
    public function create(Segmentation $segmentation)
    {
        try {
            $segmentation->setStatus(Segmentation::ACTIVE);
            $object = SegmentationHelper::validate($segmentation);
        } catch (InvalidEntityException $e) {
            throw $e;
        }
        return $this->repository->save($object);
    }
}
