<?php

namespace Wideti\DomainBundle\Service\Sms;

use Wideti\DomainBundle\Entity\SmsGateway;

interface SmsGatewayService
{
    public function update(SmsGateway $gateway);
    public function activeGateway();
}
