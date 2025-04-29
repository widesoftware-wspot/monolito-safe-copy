<?php
namespace Wideti\DomainBundle\Service\Report\Format;

use Wideti\DomainBundle\Dto\ReportDto;

interface FileFormat
{
    public function startFile($filePath, $fileName = null);
    public function addHeader(ReportDto $reportDto);
    public function addContent(ReportDto $reportDto);
    public function buildFile();
    public function clear();
}
