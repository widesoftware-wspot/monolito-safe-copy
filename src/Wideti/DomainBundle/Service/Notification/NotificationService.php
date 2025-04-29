<?php

namespace Wideti\DomainBundle\Service\Notification;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\Notification\Dto\Message;

interface NotificationService
{
    public function notify(Client $client, Message $message);
}
