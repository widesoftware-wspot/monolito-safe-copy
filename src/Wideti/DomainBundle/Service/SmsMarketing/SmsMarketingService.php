<?php

namespace Wideti\DomainBundle\Service\SmsMarketing;

use Wideti\DomainBundle\Service\SmsMarketing\Dto\SmsMarketing;
use Wideti\DomainBundle\Service\SmsMarketing\Dto\TotalGuestsFilter;

interface SmsMarketingService
{
    public function findOne($smsMarketingId);
    public function search(array $filters);
    public function prepareSearchFilters(array $filterForm);
    public function filteringTotalGuests(TotalGuestsFilter $filter);
    public function create(SmsMarketing $smsMarketing);
    public function update(SmsMarketing $smsMarketing);
    public function delete(SmsMarketing $smsMarketing);
    public function sendSmsMessage(SmsMarketing $smsMarketing);
}
