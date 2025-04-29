<?php


namespace Wideti\DomainBundle\Service\Consent;


use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Gateways\Consents\Consent;
use Wideti\DomainBundle\Gateways\Consents\Signature;

interface ConsentRequest
{
    /**
     * @param Guest $guest
     * @param Consent $consent
     * @return Signature
     */
    public function signConsent(Guest $guest, Consent $consent, $headers = []);

    /**
     * @param Guest $guest
     * @param Client $client
     * @return \Wideti\DomainBundle\Gateways\Consents\Signature
     */
    public function findSignature(Guest $guest, Client $client, $headers = []);
}
