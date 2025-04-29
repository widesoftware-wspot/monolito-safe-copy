<?php

namespace Wideti\DomainBundle\Service\UrlShortner;

interface UrlShortnerReportService
{
    public function stats($hash);
}