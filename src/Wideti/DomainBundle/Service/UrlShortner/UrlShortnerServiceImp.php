<?php

namespace Wideti\DomainBundle\Service\UrlShortner;

use GuzzleHttp\Client as GuzzleClient;
use Monolog\Logger;
use Wideti\AdminBundle\Security\IDP\UserAuthentication;
use Wideti\DomainBundle\Exception\UrlShortnerApiException;

class UrlShortnerServiceImp implements UrlShortnerService
{
    const ENDPOINT_SHORTNER = '/shorten';

    /**
     * @var Logger
     */
    private $logger;
    private $apiHost;

    /**
     * UrlShortnerReportServiceImp constructor.
     * @param Logger $logger
     * @param $apiHost
     */
    public function __construct(Logger $logger, $apiHost)
    {
        $this->logger = $logger;
        $this->apiHost = $apiHost;
    }

    /**
     * @param $url
     * @return mixed
     * @throws UrlShortnerApiException
     */
    public function shorten($url)
    {
        $uri = $this->apiHost . self::ENDPOINT_SHORTNER;

        try {
            $response = $this->request("POST", $uri, [
                "url" => $url
            ]);
        } catch (\Exception $ex) {
            throw new UrlShortnerApiException($ex->getMessage(), $url, $uri);
        }

        $statusCode = $response->getStatusCode();
        $result     = json_decode($response->getBody()->getContents(), true);

        if ($statusCode !== 200 || !isset($result["urlShortned"])) {
            throw new UrlShortnerApiException($result, $url, $uri);
        }

        return $result;
    }

    private function request($action, $uri, $body)
    {
        $httpClient = $this->getHttpClient();
        return $httpClient->request($action, $uri, [
            "body" => json_encode($body)
        ]);
    }

    /**
     * @return mixed
     */
    private function getHttpClient()
    {
        return new GuzzleClient([
            'verify' => false,
            'base_uri' => $this->apiHost,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

    }
}
