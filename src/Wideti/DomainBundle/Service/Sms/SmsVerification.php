<?php

namespace Wideti\DomainBundle\Service\Sms;

interface SmsVerification
{
    public function checkLimitSendSms();
}
