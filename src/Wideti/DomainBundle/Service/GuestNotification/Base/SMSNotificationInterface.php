<?php

namespace Wideti\DomainBundle\Service\GuestNotification\Base;

use Wideti\FrontendBundle\Factory\Nas;

interface SMSNotificationInterface
{
    public function sendSMS(Nas $nas = null, $params = []);
}
