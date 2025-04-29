<?php

namespace Wideti\DomainBundle\Service\SmsMarketing;

use Wideti\DomainBundle\Service\SmsMarketing\Dto\SmsMarketing;

interface SmsMarketingReportService
{
    public function stats(SmsMarketing $smsMarketing);
}
