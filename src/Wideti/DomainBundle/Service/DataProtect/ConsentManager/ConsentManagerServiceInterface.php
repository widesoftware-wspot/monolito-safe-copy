<?php


namespace Wideti\DomainBundle\Service\DataProtect\ConsentManager;


use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;

interface ConsentManagerServiceInterface
{
    function getConditions(Users $requester, $traceHeaders = []);
    function getLastVersionConsentClient(Client $client, Users $requester, $traceHeaders);
    function createNewVersionConsentClient(Client $client, Users $requester, array $conditionsId, $traceHeaders = []);
    function deleteConsentClient(Client $client, Users $requester, $consentId, $traceHeaders = []);
}