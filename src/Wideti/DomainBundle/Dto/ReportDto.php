<?php

namespace Wideti\DomainBundle\Dto;

class ReportDto
{
    private $columns;
    private $content;
    private $isBatch;
    private $expireDate;
    private $filePath;

    /**
     * @return mixed
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param mixed $columns
     */
    public function setColumns(array $columns)
    {
        $this->columns = $columns;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     */
    public function setContent(array $content)
    {
        $this->content = $content;
    }

    /**
     * @return mixed
     */
    public function isBatch()
    {
        return $this->isBatch;
    }

    /**
     * @param mixed $isBatch
     */
    public function setIsBatch($isBatch)
    {
        $this->isBatch = $isBatch;
    }

    /**
     * @return mixed
     */
    public function getExpireDate()
    {
        return $this->expireDate;
    }

    /**
     * @param mixed $expireDate
     */
    public function setExpireDate($expireDate)
    {
        $this->expireDate = $expireDate;
    }

    /**
     * @return mixed
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @param mixed $filePath
     */
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
    }

    public function clearContent()
    {
        $this->content = null;
    }

    public function clearColumns()
    {
        $this->columns = null;
    }
}
