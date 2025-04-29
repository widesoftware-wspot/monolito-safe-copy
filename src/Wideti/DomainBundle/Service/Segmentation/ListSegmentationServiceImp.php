<?php

namespace Wideti\DomainBundle\Service\Segmentation;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Repository\SegmentationRepository;

class ListSegmentationServiceImp implements ListSegmentationService
{
    /**
     * @var SegmentationRepository
     */
    private $repository;

    /**
     * ListSegmentationServiceImp constructor.
     * @param SegmentationRepository $repository
     */
    public function __construct(SegmentationRepository $repository)
    {
        $this->repository = $repository;
    }

    public function listAll(Client $client)
    {
        return $this->repository->findBy([
            'client' => $client
        ]);
    }
}