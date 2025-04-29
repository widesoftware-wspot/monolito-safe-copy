<?php

namespace Wideti\PanelBundle\Service;

use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client as GuzzleClient;


class WhitelabelCertService
{
    use SecurityAware;
    use LoggerAware;
    use TwigAware;

    private $guzzleClient;

    /**
     * WhitelabelCertService constructor.
     */
    public function __construct() {
        $this->guzzleClientConfig = [
            'base_uri' => 'http://puppet.wideti.com.br:8080'
        ];
    }

    public function generateCert($domain) {
        try {
            $this->guzzleClient = new GuzzleClient($this->guzzleClientConfig);
            $endpoint = "/generate-wildcard-cert?domain=" . urlencode($domain);
            return $this->guzzleClient->post($endpoint);
        }catch (RequestException $ex){
            if ($ex->getCode() == 500) { # Default error status_code for shell2http
                return $ex->getResponse();
            }
            $this->logger->addCritical($ex->getMessage());
        }
    }
}