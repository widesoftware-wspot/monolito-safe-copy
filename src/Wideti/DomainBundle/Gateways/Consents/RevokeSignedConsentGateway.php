<?php


namespace Wideti\DomainBundle\Gateways\Consents;


use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Repository\CircuitBreakerRepository;
use Wideti\DomainBundle\CircuitBreaker\CircuitBreakerException;
use Wideti\DomainBundle\Helpers\Resilience\Resilience;
use Wideti\DomainBundle\Helpers\Resilience\RetryExceededException;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;

class RevokeSignedConsentGateway
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

    public function revoke(Guest $guest, Consent $consent, $headers = []) {

        $onError = function (ClientException $ex) {
            $statusCode = $ex->getResponse()->getStatusCode();
            return $statusCode == 400 || $statusCode == 404;
        };

        $uri = "/v1/guests/{$guest->getMysql()}/signed-consents?consent_id={$consent->getId()}";
        try {
            if ($this->circuitBreakerRepository->CheckCircuitIsOpen('consent')) {
                throw new CircuitBreakerException("CircuitBreaker is open for consent, request cannot be processed.");
            }

            $resilientClient = Resilience::newClient($this->consentServiceHost)
                ->withRetry(3, 100)
                ->withTimeout(1000)
                ->addHeader("x-kind", Kinds::guest()->getValue())
                ->addHeader("x-kind-id", $guest->getId());

            foreach ($headers as $key => $value) {
            	$resilientClient->addHeader($key, $value);
			}

			$result = $resilientClient->doDELETE($uri, $onError);
            return $this->build($result);
        } catch (ClientException $e) {
            $this->logger->warning("Client error on retrieve consents from microservice: " . $e->getMessage(), $e->getHandlerContext());
            return SignedConsentRevoke::create("")->withError($e);
        } catch (RetryExceededException $e) {
            $this->circuitBreakerRepository->reportFailure('consent');
            $this->logger->error("Fail on retrieve consents from microservice: " . $e->getMessage(), $e->getTrace());
            return SignedConsentRevoke::create("")->withError($e);
        } catch(CircuitBreakerException $e) {
            $this->logger->error($e->getMessage());
            return Consent::create("")->withError($e);
        }
    }

    /**
     * @param ResponseInterface $response
     * @return SignedConsentRevoke
     */
    private function build(ResponseInterface $response) {
        $data = json_decode($response->getBody()->getContents(), true);
        $revoke = SignedConsentRevoke::create($data["message"]);
        return $revoke;
    }
}
