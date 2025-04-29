<?php

namespace Wideti\DomainBundle\Service\Segmentation\Equality\UniqueGuests;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Document\Repository\GuestRepository;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\RadacctRepository;
use Wideti\DomainBundle\Service\Segmentation\Equality\Equality;
use Wideti\DomainBundle\Service\Segmentation\Filter\Filter;
use Wideti\DomainBundle\Service\Segmentation\Filter\FilterItem;

class Range implements Equality
{
    /**
     * @var RadacctRepository
     */
    private $radacctRepository;

    /**
     * @var GuestRepository
     */
    private $guestRepository;

    /**
     * Range constructor.
     * @param RadacctRepository $radacctRepository
     * @param GuestRepository $guestRepository
     */
    public function __construct(
        RadacctRepository $radacctRepository,
        GuestRepository $guestRepository
    ) {
        $this->radacctRepository = $radacctRepository;
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
        $clientId = $filter->getClient();
        $filterType = $filter->getType();
        $filterIds = $filter->getIds();
        $period = $filterItem->getValue();

        if ($filterType == Filter::TYPE_ALL) {
            $guests = $this->radacctRepository
                ->recurringOrUniqueGuestsIds($clientId, $period, $filterIds, 'unique');
            if (count($guests) == 0) {
                $filter->setIds(null);
                $filterItem->setIsCompleted(true);
                return $filter;
            }
        } else {
            $guests = $this->radacctRepository
                ->recurringOrUniqueGuestsIds($clientId, $period, $filterIds, 'unique');
        }

        if (!$isPreview) {
            $guests = $this->guestRepository->findByIds($guests);
            return $guests;
        }

        $filter->setCount(count($guests));

        /**
         * @var Guest $guest
         */
        foreach ($guests as $guest) {
            array_push($ids, $guest);
            if ($isPreview && count($ids) === 100) break;
        }

        $filter->addIds($ids, $filterType);

        return $filter;
    }
}