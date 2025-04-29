<?php


namespace Wideti\DomainBundle\Gateways\Consents;


use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Repository\CircuitBreakerRepository;
use Wideti\DomainBundle\CircuitBreaker\CircuitBreakerException;
use Wideti\DomainBundle\Helpers\Resilience\Resilience;
use Wideti\DomainBundle\Helpers\Resilience\RetryExceededException;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;

class GetConsentGateway
{
    use LoggerAware;

    private $consentServiceHost;
    private $circuitBreakerRepository;

    /**
     * ListConsentService constructor.
     * @param $consentServiceHost
     */
    public function __construct($consentServiceHost, CircuitBreakerRepository $circuitBreakerRepository)
    {
        $this->consentServiceHost = $consentServiceHost;
        $this->circuitBreakerRepository = $circuitBreakerRepository;
    }

    public function get(Client $client, $locale = 'pt_BR', $headers = []) {

        $onError = function (ClientException $ex) {
            $statusCode = $ex->getResponse()->getStatusCode();
            return $statusCode == 400;
        };

        $uri = "/v1/clients/" . $client->getId() . "/consent";
        try {
            if ($this->circuitBreakerRepository->CheckCircuitIsOpen('consent')) {
                throw new CircuitBreakerException("CircuitBreaker is open for consent, request cannot be processed.");
            }

			$resilienceClient = Resilience::newClient($this->consentServiceHost)
				->withRetry(3, 100)
				->withTimeout(500)
				->addHeader("Accept-Language", $locale);

			foreach ($headers as $key => $value) {
				$resilienceClient->addHeader($key, $value);
			}

            $result = $resilienceClient->doGET($uri, $onError);
            return $this->build($result);
        } catch (ClientException $e) {
            $this->logger->warning("Client error on retrieve consents from microservice: " . $e->getMessage(), $e->getHandlerContext());
            return Consent::create("")->withError($e);
        } catch (RetryExceededException $e) {
            $this->circuitBreakerRepository->reportFailure('consent');
            $this->logger->error("Fail on retrieve consents from microservice: " . $e->getMessage(), $e->getTrace());
            return Consent::create("")->withError($e);
        } catch(CircuitBreakerException $e) {
            $this->logger->error($e->getMessage());
            return Consent::create("")->withError($e);
        }
    }

    /**
     * @param ResponseInterface $response
     * @return Consent
     */
    private function build(ResponseInterface $response) {
        $data = json_decode($response->getBody()->getContents(), true);
        $cons = Consent::create($data["id"])
            ->withClientId($data['client_id'])
            ->withStatus($data['status'])
            ->withVersion($data['consent_version']);
        foreach ($data['conditions'] as $c) {
            $cons->addCondition(Condition::create($c['id'], $c['description']));
        }
        return $cons;
    }
}
