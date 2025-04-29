<?php
namespace Wideti\DomainBundle\Service\Report\Format;

use Dompdf\Dompdf;
use Wideti\DomainBundle\Dto\ReportDto;
use Wideti\DomainBundle\Service\Report\ReportFormat;

class PdfFormat implements FileFormat
{
    /**
     * @var Dompdf
     */
    private $pdfGenerator;
    private $fileUri;
    private $htmlTable;

    public function startFile($filePath, $fileName = null)
    {
        if (empty($fileName)) {
            $this->fileUri = $filePath . uniqid() . "." . strtolower(ReportFormat::PDF);
        } else {
            $this->fileUri = $filePath . $fileName . "." . strtolower(ReportFormat::PDF);
        }

        $this->htmlTable .= "<table style='border-collapse: collapse;border: 1px solid black; width: 100%;'>";
        $this->pdfGenerator = new Dompdf();
    }

    public function addHeader(ReportDto $reportDto)
    {
        if (!empty($reportDto->getColumns())) {
            $this->htmlTable .= "<tr style='border: 1px solid black;'>";
            foreach ($reportDto->getColumns() as $row) {
                $this->htmlTable .= "<th style='border: 1px solid black;'>{$row}</th>";
            }
            $this->htmlTable .= "</tr>";
        }
    }

    public function addContent(ReportDto $reportDto)
    {
        if (!empty($reportDto->getContent())) {
            foreach ($reportDto->getContent() as $row) {
                $this->htmlTable .= "<tr style='border: 1px solid black;'>";
                foreach ($row as $cell) {
                    $this->htmlTable .= "<td style='border: 1px solid black;'>{$cell}</td>";
                }
                $this->htmlTable .= "</tr>";
            }
        }
    }

    public function buildFile()
    {
        $this->htmlTable .= "</table>";
        $this->pdfGenerator->loadHtml($this->htmlTable);
        $this->pdfGenerator->setPaper('A4', 'landscape');
        $this->pdfGenerator->render();
        $output = $this->pdfGenerator->output();
        file_put_contents($this->fileUri, $output);
        return $this->fileUri;
    }

    public function clear()
    {
        unlink($this->fileUri);
    }
}
