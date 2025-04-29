<?php

namespace Wideti\DomainBundle\Service\GuestNotification\Base;

use Wideti\FrontendBundle\Factory\Nas;

interface EmailNotificationInterface
{
    public function send(Nas $nas = null, $params = []);
}
