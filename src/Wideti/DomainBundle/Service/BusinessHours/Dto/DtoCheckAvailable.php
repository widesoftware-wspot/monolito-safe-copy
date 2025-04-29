<?php

namespace Wideti\DomainBundle\Service\BusinessHours\Dto;

class DtoCheckAvailable
{
    protected $isAvailable;

    protected $startHour;

    protected $endHour;

    public function __construct($isAvailable = null, $periods = [])
    {
        $this->isAvailable = $isAvailable;
        file_put_contents("/sites/wspot.com.br/app/logs/dev.log", "periods: " . json_encode($periods) . PHP_EOL, FILE_APPEND);
        $this->periods = $periods;
    }

    public function isAvailable()
    {
        return $this->isAvailable;
    }

    public function startHour()
    {
        return $this->startHour;
    }

    public function endHour()
    {
        return $this->endHour;
    }
}