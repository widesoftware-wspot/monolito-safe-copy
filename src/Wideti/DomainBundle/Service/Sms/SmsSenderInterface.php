<?php

namespace Wideti\DomainBundle\Service\Sms;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Service\Sms\Dto\SmsDto;

interface SmsSenderInterface
{
    public function send(SmsDto $sms, Guest $guest, $phoneNumber);
    public function saveHistory($history, SmsDto $sms);
}
