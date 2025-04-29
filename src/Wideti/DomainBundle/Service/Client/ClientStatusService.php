<?php

namespace Wideti\DomainBundle\Service\Client;

use Wideti\DomainBundle\Service\Client\Dto\ClientStatusDto;

interface ClientStatusService
{
    public function changeStatus(ClientStatusDto $clientStatus);
}
