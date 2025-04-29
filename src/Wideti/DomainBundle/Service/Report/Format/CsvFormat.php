<?php
namespace Wideti\DomainBundle\Service\Report\Format;

use Wideti\DomainBundle\Dto\ReportDto;
use Wideti\DomainBundle\Service\Report\ReportFormat;

class CsvFormat implements FileFormat
{
    private $fileHandle;
    private $fileUri;

    public function startFile($filePath, $fileName = null)
    {
        if (empty($fileName)) {
            $fileName = uniqid();
        }

        $this->fileUri = $filePath . $fileName . "." . strtolower(ReportFormat::CSV);
    }

    public function addHeader(ReportDto $reportDto)
    {
        $this->fileHandle = fopen($this->fileUri, 'a');
        $columns = $reportDto->getColumns();
        if (!empty($columns)) {
            fputcsv($this->fileHandle, $columns, ';');
        }
        fclose($this->fileHandle);
    }

    public function addContent(ReportDto $reportDto)
    {
        $this->fileHandle = fopen($this->fileUri, 'a');
        $content = $reportDto->getContent();
        if (!empty($content)) {
            foreach ($content as $row) {
                fputcsv($this->fileHandle, $row, ';');
            }
        }
        fclose($this->fileHandle);
    }

    public function buildFile()
    {
        return $this->fileUri;
    }

    public function clear()
    {
        unlink($this->fileUri);
    }
}
