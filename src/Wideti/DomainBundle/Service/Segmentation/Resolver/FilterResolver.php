<?php

namespace Wideti\DomainBundle\Service\Segmentation\Resolver;

use Monolog\Logger;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Wideti\DomainBundle\Service\Segmentation\Equality\Equality;
use Wideti\DomainBundle\Service\Segmentation\Equality\EqualityFactory;
use Wideti\DomainBundle\Service\Segmentation\Filter\Dto\FilterDto;
use Wideti\DomainBundle\Service\Segmentation\Filter\Filter;
use Wideti\DomainBundle\Service\Segmentation\Filter\FilterItem;

class FilterResolver implements SegmentationResolver
{
    /**
     * @var EqualityFactory
     */
    private $equalityFactory;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * FilterNameResolver constructor.
     * @param EqualityFactory $equalityFactory
     * @param Logger $logger
     */
    public function __construct(EqualityFactory $equalityFactory, Logger $logger)
    {
        $this->equalityFactory = $equalityFactory;
        $this->logger = $logger;
    }

    public function resolve(FilterDto $filterDto, $isPreview = false, $params = [])
    {
        $filter = $this->initFilter($filterDto);

        /**
         * @var FilterItem $item
         * @var Equality $dataSource
         */
        foreach ($filter->getItems() as $item) {
            try {
                $dataSource = $this->equalityFactory->get($item->getIdentifier(), $item->getEquality());
            } catch (ServiceNotFoundException $ex) {
                $service = strtolower("{$item->getIdentifier()}:{$item->getEquality()}");
                $this->logger->addCritical("Segmentation - equality not found: {$service}");
                throw $ex;
            } catch (\Exception $ex) {
                $this->logger->addCritical("Segmentation - an error occurred while resolve filters: {$ex->getMessage()}");
                return throwException($ex);
            }

            $filter = $dataSource->search($filter, $item, $isPreview, $params);

            if ($item->isCompleted()) {
                break;
            }
        }

        return $filter;
    }

    private function initFilter(FilterDto $filterDto)
    {
        $filter = new Filter();
        $filter->setType($filterDto->getType());
        $filter->setClient($filterDto->getClient());

        /**
         * @var FilterDto $objItem
         */
        foreach ($filterDto->getItems() as $objItem) {
            $item = new FilterItem();
            $item->setIdentifier($objItem->getIdentifier());
            $item->setEquality($objItem->getEquality());
            $item->setType($objItem->getType());
            $item->setValue($objItem->getValue());
            $filter->addItem($item);
        }

        return $filter;
    }
}
