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

class PostConsentGateway
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
    public function post(Client $client, array $conditionList, $locale = 'pt_BR', $headers = []) {

        $onError = function (ClientException $ex) {
            $statusCode = $ex->getResponse()->getStatusCode();
            return $statusCode == 400 || $statusCode == 409;
        };

        $body = [
            'client_id' => $client->getId(),
            'conditions' => $conditionList
        ];

        $uri = "/v1/consents";
        try {
            if ($this->circuitBreakerRepository->CheckCircuitIsOpen('consent')) {
                throw new CircuitBreakerException("CircuitBreaker is open for consent, request cannot be processed.");
            }

            $resilienceClient = Resilience::newClient($this->consentServiceHost)
                ->withRetry(3, 100)
                ->withTimeout(1000)
                ->addHeader("x-kind", Kinds::client()->getValue())
                ->addHeader("x-kind-id", $client->getId())
                ->addHeader("Accept-Language", $locale)
                ->addBody($body);

            foreach ($headers as $key => $value) {
            	$resilienceClient->addHeader($key, $value);
			}

            $result = $resilienceClient->doPOST($uri, $onError);
            return $this->build($result);
        } catch (ClientException $e) {
            $this->logger->warning("Client error on create consents from microservice: " . $e->getMessage(), $e->getHandlerContext());
            return Consent::create("")->withError($e);
        } catch (RetryExceededException $e) {
            $this->circuitBreakerRepository->reportFailure('consent');
            $this->logger->error("Fail on create consents from microservice: " . $e->getMessage(), $e->getTrace());
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
