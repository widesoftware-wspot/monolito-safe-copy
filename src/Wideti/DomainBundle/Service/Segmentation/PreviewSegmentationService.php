<?php

namespace Wideti\DomainBundle\Service\Segmentation;

use Wideti\DomainBundle\Service\Segmentation\Filter\Dto\FilterDto;

interface PreviewSegmentationService
{
    public function preview(FilterDto $filter);
}