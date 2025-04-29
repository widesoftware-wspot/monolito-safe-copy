<?php

namespace Wideti\DomainBundle\Service\BouncedValidation;

use GuzzleHttp\Client as GuzzleClient;
use Monolog\Logger;

class BouncedValidationImp implements BouncedValidation
{
    private $apiKey;
    private $ticketAPI;
    /**
     * @var string
     */
    private $bounceValidatorServiceUri;

    /**
     * @var bool
     */
    private $shouldUseSafetyMails;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * BouncedValidationImp constructor.
     * @param $apiKey
     * @param $ticketAPI
     * @param Logger $logger
     * @param $bounceValidatorServiceUri
     * @param $shouldUseSafetyMails
     */
    public function __construct($apiKey, $ticketAPI, Logger $logger, $bounceValidatorServiceUri, $shouldUseSafetyMails)
    {
        $this->apiKey       = $apiKey;
        $this->ticketAPI    = $ticketAPI;
        $this->logger       = $logger;
        $this->bounceValidatorServiceUri = $bounceValidatorServiceUri;
        $this->shouldUseSafetyMails = $shouldUseSafetyMails;
    }

    /**
     * Validação de email com SafetyMails
     * Status retornados: VALIDO, INVALIDO e PENDENTE
     * @param $email
     * @return bool
     */
    public function isValid($email)
    {
        $curl = new GuzzleClient([
            'base_uri'      => $this->bounceValidatorServiceUri,
            'http_errors'   => false,
            'verify'        => false
        ]);

        if ($this->shouldUseSafetyMails) {
            return $this->checkBySafetyMails($curl, $email);
        }

        return $this->checkByBounceValidatorService($curl, $email);

    }

    private function checkBySafetyMails($curl, $email) {
        $emailEncoded = base64_encode($email);
        $response   = $curl->get("main/safetyapi/{$this->apiKey}/{$this->ticketAPI}/{$emailEncoded}");
        $result     = json_decode($response->getBody()->getContents(), true);

        if ($result['Success'] == "false" || $result['Status'] != "VALIDO") {
            $this->logger->addWarning("Retorno SafetyMails: " . json_encode($result));
        }

        if ($result['Status'] == "INVALIDO" || $result['Status'] == "PENDENTE" || $result['Success'] == "false" ) {
            return false;
        }

        return true;
    }

    private function checkByBounceValidatorService($curl, $email) {
        $response   = $curl->get("validate/{$email}");

        if ( $response->getStatusCode() == 400 ) {
            return false;
        }

        return true;
    }
}
