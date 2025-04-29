<?php

namespace Wideti\DomainBundle\Service\HttpRequest;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Wideti\DomainBundle\Service\HttpRequest\Dto\HttpResponse;

class HttpRequestServiceImp implements HttpRequestService
{
    /**
     * @var Client
     */
    private $httpClient;

    public function __construct(Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param $url
     * @param $headers
     * @return HttpResponse
     */
    public function get($url, $headers)
    {
        $expectionResponse = null;

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => $headers
            ]);

            return new HttpResponse($response->getStatusCode(), $this->getContent($response));
        } catch (ConnectException $e) {
            return new HttpResponse(500, $e->getMessage());
        } catch (ClientException $e) {
            $expectionResponse = $e->getResponse();
        } catch (RequestException $e) {
            $expectionResponse = $e->getResponse();
        }

        if (empty($expectionResponse)) {
            return new HttpResponse(500, null);
        }
        return new HttpResponse($expectionResponse->getStatusCode(), $this->getContent($expectionResponse));
    }

    /**
     * @param $url
     * @param $headers
     * @param $body
     * @return mixed
     */
    public function post($url, $headers, $body)
    {
        $expectionResponse = null;

        try {
            $response = $this->httpClient->post($url, [
                'headers'   => $headers,
                'body'      => json_encode($body)
            ]);

            return new HttpResponse($response->getStatusCode(), $this->getContent($response));
        } catch (ConnectException $e) {
            return new HttpResponse(500, $e->getMessage());
        } catch (ClientException $e) {
            $expectionResponse = $e->getResponse();
        } catch (RequestException $e) {
            $expectionResponse = $e->getResponse();
        }

        if (empty($expectionResponse)) {
            return new HttpResponse(500, null);
        }
        return new HttpResponse($expectionResponse->getStatusCode(), $this->getContent($expectionResponse));
    }

    /**
     * @param $url
     * @param $headers
     * @param $body
     * @return mixed|HttpResponse
     */
    public function put($url, $headers, $body)
    {
        $expectionResponse = null;

        try {
            $response = $this->httpClient->put($url, [
                'headers'   => $headers,
                'body'      => json_encode($body)
            ]);

            return new HttpResponse($response->getStatusCode(), $this->getContent($response));
        } catch (ConnectException $e) {
            return new HttpResponse(500, $e->getMessage());
        } catch (ClientException $e) {
            $expectionResponse = $e->getResponse();
        } catch (RequestException $e) {
            $expectionResponse = $e->getResponse();
        }

        if (empty($expectionResponse)) {
            return new HttpResponse(500, null);
        }
        return new HttpResponse($expectionResponse->getStatusCode(), $this->getContent($expectionResponse));
    }

    /**
     * @param ResponseInterface $response
     * @return mixed
     */
    private function getContent(ResponseInterface $response)
    {
        $jsonResponse = $response->getBody()->getContents();
        return json_decode($jsonResponse);
    }
}
