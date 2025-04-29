<?php

namespace Wideti\DomainBundle\Service\Segmentation\Resolver;

use Wideti\DomainBundle\Service\Segmentation\Filter\Dto\FilterDto;

interface SegmentationResolver
{
    public function resolve(FilterDto $filterDto);
}
