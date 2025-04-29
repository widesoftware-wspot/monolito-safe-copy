<?php

namespace Wideti\DomainBundle\Service\ClientLogs;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\ClientLogs\Dto\ClientLogDto;
use Wideti\DomainBundle\Service\ClientLogs\Dto\ClientOptionsGetLogDto;

interface ClientLogsService
{
    /**
     * @param ClientLogDto $log
     * @return mixed
     */
    public function log(ClientLogDto $log);

    /**
     * @param Client $client
     * @param $action
     * @return mixed
     */
    public function logClientSettlementCharge(Client $client, $action);

    public function getLogsBy(ClientOptionsGetLogDto $options);
}
