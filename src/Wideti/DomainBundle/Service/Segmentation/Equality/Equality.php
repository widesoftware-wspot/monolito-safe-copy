<?php

namespace Wideti\DomainBundle\Service\Segmentation\Equality;

use Wideti\DomainBundle\Service\Segmentation\Filter\Filter;
use Wideti\DomainBundle\Service\Segmentation\Filter\FilterItem;

interface Equality
{
    public function search(Filter $filter, FilterItem $filterItem, $isPreview = false, $params = []);
}
