<?php

namespace Wideti\DomainBundle\Service\Sms;

use Wideti\ApiBundle\Helpers\Dto\SmsCallbackDto;

interface SmsHistory
{
    public function updateHistoryWithCallback(SmsCallbackDto $callbackDto);
}
