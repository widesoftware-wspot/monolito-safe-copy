<?php


namespace Wideti\DomainBundle\Gateways\Sessions;


use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;
use Wideti\DomainBundle\Helpers\Resilience\Resilience;
use Wideti\DomainBundle\Helpers\Resilience\RetryExceededException;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;

class PostSessionGateway
{
    use LoggerAware;

    private $sessionServiceHost;

    /**
     * ListConsentService constructor.
     * @param $sessionServiceHost
     */
    public function __construct($sessionServiceHost)
    {
        $this->sessionServiceHost = $sessionServiceHost;
    }
    public function post(array $sessionPolicy, $locale = 'pt_BR', $headers = []) {

        $onError = function (ClientException $ex) {
            $statusCode = $ex->getResponse()->getStatusCode();
            return $statusCode == 400 || $statusCode == 409;
        };

        $body = $sessionPolicy;

        $uri = "/sessions";
        try {
            $resilienceClient = Resilience::newClient($this->sessionServiceHost)
                ->withRetry(3, 100)
                ->withTimeout(1000)
                ->addHeader("Accept-Language", $locale)
                ->addBody($body);

            foreach ($headers as $key => $value) {
            	$resilienceClient->addHeader($key, $value);
			}

            $result = $resilienceClient->doPOST($uri, $onError);

            return $this->build($result);
        } catch (ClientException $e) {
            $this->logger->warning("Client error on create session from microservice: " . $e->getMessage(), $e->getHandlerContext());
            return SessionResponse::create("")->withError($e);
        } catch (RetryExceededException $e) {
            $this->logger->error("Fail on create session from microservice: " . $e->getMessage(), $e->getTrace());
            return SessionResponse::create("")->withError($e);
        }
    }

    /**
     * @param ResponseInterface $response
     * @return Consent
     */
    private function build(ResponseInterface $response) {
        $data = json_decode($response->getBody()->getContents(), true);
        $session = SessionResponse::create($data["id"]);
        return $session;
    }
}
