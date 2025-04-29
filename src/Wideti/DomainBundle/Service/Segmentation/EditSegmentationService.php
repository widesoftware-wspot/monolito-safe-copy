<?php

namespace Wideti\DomainBundle\Service\Segmentation;

use Wideti\DomainBundle\Entity\Segmentation;

interface EditSegmentationService
{
    public function edit(Segmentation $segmentation);
}
