<?php

namespace Wideti\DomainBundle\Service\Segmentation\Equality\Visits;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Document\Repository\GuestRepository;
use Wideti\DomainBundle\Service\Segmentation\Equality\Equality;
use Wideti\DomainBundle\Service\Segmentation\Filter\Filter;
use Wideti\DomainBundle\Service\Segmentation\Filter\FilterItem;

class Range implements Equality
{
    /**
     * @var GuestRepository
     */
    private $guestRepository;

    /**
     * Is constructor.
     * @param GuestRepository $guestRepository
     */
    public function __construct(GuestRepository $guestRepository)
    {
        $this->guestRepository = $guestRepository;
    }

    /**
     * @param Filter $filter
     * @param FilterItem $filterItem
     * @param bool $isPreview
     * @param array $params
     * @return Filter
     */
    public function search(Filter $filter, FilterItem $filterItem, $isPreview = false, $params = [])
    {
        $ids = [];

        if ($filter->getType() == Filter::TYPE_ALL) {
            $guests = $this->guestRepository->segmentFindByLastAcessRange($filterItem->getValue(), $filter->getIds(), $params);

            if (count($guests) == 0) {
                $filter->setIds(null);
                $filterItem->setIsCompleted(true);
                return $filter;
            }
        } else {
            $guests = $this->guestRepository->segmentFindByLastAcessRange($filterItem->getValue(), [], $params);
        }

        if (!$isPreview) {
            return $guests;
        }

        $filter->setCount(count($guests));

        /**
         * @var Guest $guest
         */
        foreach ($guests as $guest) {
            array_push($ids, $guest->getMySql());
            if ($isPreview && count($ids) === 100) break;
        }

        $filter->addIds($ids, $filter->getType());

        return $filter;
    }
}