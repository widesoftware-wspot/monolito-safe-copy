<?php

namespace Wideti\DomainBundle\Service\Segmentation;

use Wideti\DomainBundle\Service\Segmentation\Dto\ExportDto;

interface ExportSegmentationService
{
    public function requestingExport(ExportDto $exportDto);
}
