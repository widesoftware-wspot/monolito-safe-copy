<?php

namespace Wideti\DomainBundle\Service\ClientSelector;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Exception\ClientWasDisabledException;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class ClientSelectorService
{
    use EntityManagerAware;
    use SessionAware;
	private $environmentsAvailableSubdomains;

	/**
	 * ClientSelectorService constructor.
	 * @param $environmentsAvailableSubdomains
	 */
	public function __construct($environmentsAvailableSubdomains)
	{
		$this->environmentsAvailableSubdomains = $environmentsAvailableSubdomains;
	}

	/**
     * @param $fullDomain
     * @return Client
     */
    public function define($fullDomain)
    {
        $domain = $fullDomain;
        if(strpos($fullDomain, "wspot.com.br") || strpos($fullDomain, "mambowifi") ) {
            $domain = $this->replaceFullDomainToGetDomain($fullDomain);
        }

	    $savedClient = $this->session->get("wspotClient");

        if ($savedClient instanceof Client) {
            if ($savedClient->getDomain() == $domain) {
                return $savedClient;
            }
        }

        $client = $this->em
            ->getRepository('DomainBundle:Client')
            ->findOneByDomain($domain)
        ;


        if ($client == null) {
            throw new NotFoundHttpException('Domain not found');
        }

        if ($client->getStatus() == Client::STATUS_INACTIVE) {
            throw new ClientWasDisabledException('This client was disabled!');
        }

        $this->session->set('wspotClient', $client);

        return $client;
    }

	private function replaceFullDomainToGetDomain($fullDomain)
	{

	    if (strpos($fullDomain, 'mambowifi')) {
            $fullSubDomain = explode(".mambowifi.com", $fullDomain);
        } else {
            $fullSubDomain = explode(".wspot.com.br", $fullDomain);
        }

	    if (strpos($fullSubDomain[0], '.') !== false) {
	    	$subDomain = explode(".", $fullSubDomain[0]);

	    	if (!in_array($subDomain[1], $this->environmentsAvailableSubdomains)) {
			    throw new NotFoundHttpException('Domain not found');
		    }

	    	return $subDomain[0];
	    }

		return $fullSubDomain[0];
	}
}
