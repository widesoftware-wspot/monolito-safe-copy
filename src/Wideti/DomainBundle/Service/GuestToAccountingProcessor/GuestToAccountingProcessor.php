<?php

namespace Wideti\DomainBundle\Service\GuestToAccountingProcessor;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Dto\ObjectToQueueDto;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Dto\RequestDto;

interface GuestToAccountingProcessor
{
    public function process(Client $client, $guest);
    public function send(RequestDto $object);
}
