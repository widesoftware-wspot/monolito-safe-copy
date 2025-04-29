<?php
namespace Wideti\DomainBundle\Service\ReportFileBuilder;

use Symfony\Component\HttpFoundation\Response;
use Wideti\DomainBundle\Dto\ReportDto;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Service\Report\Format\FileFormat;
use Wideti\DomainBundle\Service\Report\ReportFormat;

class ReportFileBuilder
{
    /**
     * @var FileFormat
     */
    private $fileFormat;

    /**
     * @var FileUpload
     */
    private $fileUpload;
    /**
     * @var string
     */
    private $format;

    public function __construct(FileUpload $fileUpload, $filePath, $format = ReportFormat::CSV)
    {
        $this->fileUpload = $fileUpload;

        $clazz = new \ReflectionClass('Wideti\DomainBundle\Service\Report\Format\\' . $format . "Format");
        $this->fileFormat = $clazz->newInstance();
        $this->fileFormat->startFile($filePath);
        $this->format = $format;
    }

    public function addContent(ReportDto $dto)
    {
        if (!empty($dto->getColumns())) {
            $this->fileFormat->addHeader($dto);
        }

        if (!empty($dto->getContent())) {
            $this->fileFormat->addContent($dto);
        }
    }

    public function build()
    {
        return $this->fileFormat->buildFile();
    }

    public function clear()
    {
        return $this->fileFormat->clear();
    }
}
