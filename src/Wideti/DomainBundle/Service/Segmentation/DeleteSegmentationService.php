<?php

namespace Wideti\DomainBundle\Service\Segmentation;

use Wideti\DomainBundle\Entity\Segmentation;

interface DeleteSegmentationService
{
    public function delete(Segmentation $segmentation);
}
