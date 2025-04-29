<?php
namespace Wideti\DomainBundle\Service\Report\Format;

use Wideti\DomainBundle\Dto\ReportDto;
use Wideti\DomainBundle\Service\Report\ReportFormat;

class XlsxFormat implements FileFormat
{
    private $phpExcel;
    private $fileHandle;
    private $fileUri;
    private $rowNumber;

    private $teste = [];

    public function startFile($filePath, $fileName = null)
    {
        /**
         * var PHPExcel
         */
        $this->phpExcel     = new \PHPExcel();
        $this->rowNumber    = 1;

        if (empty($fileName)) {
            $fileName = uniqid();
        }

        $this->phpExcel->setActiveSheetIndex(0);

        $this->phpExcel->getProperties()
            ->setCreator('Leonardo Fuzeto')
            ->setLastModifiedBy('Leonardo Fuzeto')
            ->setTitle('Relatorio de visitantes')
            ->setSubject('Relatorio de visitantes')
            ->setDescription('Relatorio de visitantes')
            ->setKeywords('office 2007 openxml php')
            ->setCategory('Test result file');

        $this->fileUri = $filePath . $fileName . "." .strtolower(ReportFormat::XLSX);
    }

    public function addHeader(ReportDto $reportDto)
    {
        $len = 0;
        for ($column = 'A'; $column <= 'Z'; $column++) {
            if ($len == count($reportDto->getColumns())) {
                break;
            }

            $this->phpExcel->getActiveSheet()
                ->setCellValueExplicit("{$column}1", $reportDto->getColumns()[$len])
            ;
            $len++;
        }

        $this->rowNumber++;
    }

    public function addContent(ReportDto $reportDto)
    {
        $content = $reportDto->getContent();

        if (!empty($content)) {
            foreach ($content as $lines) {
                $column = 'A';
                foreach ($lines as $cell) {
                    $this->phpExcel->getActiveSheet()
                        ->setCellValueExplicit("{$column}{$this->rowNumber}", $cell);
                    $column++;
                }
                $this->rowNumber++;
            }
        }
    }

    public function buildFile()
    {
        $this->phpExcel->getActiveSheet()->setTitle('Export');
        $this->phpExcel->setActiveSheetIndex(0);

        $writer = \PHPExcel_IOFactory::createWriter($this->phpExcel, 'Excel2007');
        $writer->save($this->fileUri);

        return $this->fileUri;
    }

    public function clear()
    {
        unlink($this->fileUri);
    }
}
