<?php


namespace Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentGatewayClient;


use Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentGatewayClient\Dto\ConsentParams;

interface ConsentGatewayInterface
{
    function getConditions(ConsentParams $params);
    function getConsentClient(ConsentParams $params);
    function postConsentClient(ConsentParams $params);
    function deleteConsentClient(ConsentParams $params);
}