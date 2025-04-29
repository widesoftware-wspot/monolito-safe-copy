<?php

namespace Wideti\DomainBundle\Service\SmsCredit;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\SmsCredit;
use Wideti\DomainBundle\Entity\SmsCreditHistoric;

interface SmsCreditService
{
    public function add(SmsCredit $credit, $creditAmount);
    public function remove(SmsCreditHistoric $historic);
    public function getHistoric(Client $client);
    public function getHistoricById($id);

    /**
     * @param $clientId
     * @return SmsCredit
     */
    public function getAvailableClientCredit($clientId);
    public function checkIfClientHasEnoughCreditAvailable(Client $client, $totalSmsToSend);
    public function consume(Client $client, $totalConsumedSms);
}
