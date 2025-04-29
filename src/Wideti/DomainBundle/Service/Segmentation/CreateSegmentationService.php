<?php

namespace Wideti\DomainBundle\Service\Segmentation;

use Wideti\DomainBundle\Entity\Segmentation;

interface CreateSegmentationService
{
    public function create(Segmentation $segmentation);
}
