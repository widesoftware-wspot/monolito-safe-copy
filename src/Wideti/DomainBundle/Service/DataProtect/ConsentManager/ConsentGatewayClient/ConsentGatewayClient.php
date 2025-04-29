<?php


namespace Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentGatewayClient;


use GuzzleHttp\Exception\RequestException;
use Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentGatewayClient\Dto\ConsentParams;
use GuzzleHttp\Client as GuzzleClient;
use Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentGatewayClient\Exception\ClientException;

class ConsentGatewayClient implements ConsentGatewayInterface
{
    private $guzzleClient;

    public function __construct($consentGatewayUrl)
    {
        $this->guzzleClient = new GuzzleClient([
            "base_uri" => $consentGatewayUrl,
            'defaults' => [
                'exceptions' => false,
            ],
            'headers' => ['Content-Type' => 'application/json']
        ]);
    }

    function getConditions(ConsentParams $params)
    {
        try {
            $response = $this->guzzleClient
                ->get("/v1/conditions",
                    [
                        "query" => $params->getQuerySearch(),
                        "headers" => $params->getHeaders()
                    ]
                );
            $jsonString = $response->getBody()->getContents();
            $json = json_decode($jsonString, true);
            return $json;
        }catch (RequestException $ex){
            throw $this->handleException($ex);
        }
    }

    function getConsentClient(ConsentParams $params)
    {
        try {
            $response = $this->guzzleClient
                ->get("/v1/clients/{$params->getClientID()}/consent", ["headers" => $params->getHeaders()]);
            $jsonString = $response->getBody()->getContents();
            $json = json_decode($jsonString, true);
            return $json;
        }catch (RequestException $ex){
            throw $this->handleException($ex);
        }
    }

    function postConsentClient(ConsentParams $params)
    {
        try {
            $response = $this->guzzleClient->post("/v1/consents", [
                "body" => json_encode($params->getBody()),
                "headers" => $params->getHeaders()
            ]);
            $jsonString = $response->getBody()->getContents();
            $json = json_decode($jsonString, true);
            return $json;
        }catch (RequestException $ex){
            throw $this->handleException($ex);
        }
    }

    private function handleException(RequestException $exception){
        if (is_null($exception->getResponse())){
            return new ClientException(500, ["message" => $exception->getMessage()]);
        }
        $responseError = $exception->getResponse()
            ->getBody()->getContents();
        $jsonError = json_decode($responseError, true);
        return new ClientException($exception->getCode(), $jsonError);
    }

    function deleteConsentClient(ConsentParams $params)
    {
        try {
            $this->guzzleClient
                ->delete("/v1/consents/{$params->getConsentId()}", ["headers" => $params->getHeaders()]);
        }catch (RequestException $ex){
            throw $this->handleException($ex);
        }
    }
}