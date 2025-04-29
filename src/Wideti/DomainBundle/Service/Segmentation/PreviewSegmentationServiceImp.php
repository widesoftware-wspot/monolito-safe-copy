<?php

namespace Wideti\DomainBundle\Service\Segmentation;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Document\Repository\GuestRepository;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsService;
use Wideti\DomainBundle\Service\Segmentation\Filter\Dto\FilterDto;
use Wideti\DomainBundle\Service\Segmentation\Filter\Filter;
use Wideti\DomainBundle\Service\Segmentation\Resolver\FilterResolver;

class PreviewSegmentationServiceImp implements PreviewSegmentationService
{
    /**
     * @var FilterResolver
     */
    private $filterResolver;
    /**
     * @var GuestRepository
     */
    private $guestRepository;
    /**
     * @var CustomFieldsService
     */
    private $customFieldsService;

    /**
     * PreviewSegmentationImp constructor.
     * @param FilterResolver $filterResolver
     * @param GuestRepository $guestRepository
     * @param CustomFieldsService $customFieldsService
     */
    public function __construct(
        FilterResolver $filterResolver,
        GuestRepository $guestRepository,
        CustomFieldsService $customFieldsService
    )
    {
        $this->filterResolver = $filterResolver;
        $this->guestRepository = $guestRepository;
        $this->customFieldsService = $customFieldsService;
    }

    /**
     * @param FilterDto $filter
     * @return array
     * @throws \Exception
     */
    public function preview(FilterDto $filter)
    {
        return $this->format($this->filterResolver->resolve($filter, true));
    }

    /**
     * @param Filter $filter
     * @return array
     * @throws \Exception
     */
    private function format(Filter $filter)
    {
        $ids = array_unique($filter->getIds());

        $sample = [
            'count'   => $filter->getCount(),
            'preview' => []
        ];

        $guests = $this->guestRepository->findByIds(array_slice($ids, 0, 10));
        $loginField = $this->customFieldsService->getLoginField();
        $loginField = ($loginField) ? $loginField[0]->getIdentifier() : null;

        /**
         * @var Guest $guest
         */
        foreach ($guests as $guest) {
            $sample['preview'][] = [
                'id'    => $guest->getMysql(),
                'field' => array_key_exists($loginField, $guest->getProperties())
                    ? $guest->getProperties()[$loginField]
                    : $guest->getId()
            ];
        }

        return $sample;
    }
}