<?php


namespace Wideti\DomainBundle\Service\Consent;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Consents;
use Wideti\DomainBundle\Exception\ConsentErrorException;
use Wideti\DomainBundle\Gateways\Consents\Consent;
use Wideti\DomainBundle\Gateways\Consents\GetConsentGateway;
use Wideti\DomainBundle\Gateways\Consents\ListSignatureGateway;

class ConsentRequestImp implements ConsentRequest
{

    /**
     * @var GetConsentGateway
     */
    private $getConsentGateway;

    /**
     * @var ListSignatureGateway
     */
    private $listSignatureGateway;

    /**
     * ConsentRequestImp constructor.
     * @param GetConsentGateway $getConsentGateway
     * @param ListSignatureGateway $listSignatureGateway
     */
    public function __construct(
		GetConsentGateway $getConsentGateway,
        ListSignatureGateway $listSignatureGateway)
    {
        $this->getConsentGateway = $getConsentGateway;
        $this->listSignatureGateway = $listSignatureGateway;
    }

    /**
     * @param Guest $guest
     * @param Consent $consent
     * @return mixed|\Wideti\DomainBundle\Gateways\Consents\Signature
     */
    public function signConsent(Guest $guest, Consent $consent, $headers = [])
    {
        return $this->listSignatureGateway->post($guest, $consent, 'pt_BR', $headers);
    }

    /**
     * @param Guest $guest
     * @param Client $client
     * @return \Wideti\DomainBundle\Gateways\Consents\Signature
     */
    public function findSignature(Guest $guest, Client $client, $headers = [])
    {
        $consent = $this->getConsentGateway->get($client, 'pt_BR', $headers);

        if(!is_null($consent->getError()) || is_null($consent)) {
            throw new ConsentErrorException("findSignature: fail to find consent for client: {$client->getId()}");
        }

        return $this->listSignatureGateway->get($guest, $consent, 'pt_BR', $headers);
    }
}
