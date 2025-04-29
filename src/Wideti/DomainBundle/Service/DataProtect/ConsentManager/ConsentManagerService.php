<?php


namespace Wideti\DomainBundle\Service\DataProtect\ConsentManager;


use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentGatewayClient\ConsentGatewayClient;
use Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentGatewayClient\ConsentGatewayClientMock;
use Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentGatewayClient\ConsentGatewayInterface;
use Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentGatewayClient\Dto\Body;
use Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentGatewayClient\Dto\ConsentParams;
use Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentGatewayClient\Dto\Header;
use Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentGatewayClient\Dto\Query;

class ConsentManagerService implements ConsentManagerServiceInterface
{
    /**
     * @var ConsentGatewayInterface
     */
    private $consentGateway;

    public function __construct($consentGatewayUrl)
    {
        if ($consentGatewayUrl == "consent_gateway_mock"){
            $this->consentGateway = new ConsentGatewayClientMock();
        }else{
            $this->consentGateway = new ConsentGatewayClient($consentGatewayUrl);
        }
    }

    function getConditions(Users $requester, $traceHeaders = [])
    {
        $header = Header::build()
            ->addXKind(Kinds::userAdmin())
            ->addXKindId($requester->getId())
			->addTraceHeaders($traceHeaders);
        $query = Query::build()
            ->addConditionType("client");
        $params = ConsentParams::newParams()
            ->withHeader($header)
            ->withQuery($query);
        return $this->consentGateway->getConditions($params);
    }

	/**
	 * @param Client $client
	 * @param Users $requester
	 * @param $traceHeaders
	 * @return array|mixed
	 * @throws ConsentGatewayClient\Exception\ClientException
	 */
    function getLastVersionConsentClient(Client $client, Users $requester, $traceHeaders)
    {
        $header = Header::build()
			->addTraceHeaders($traceHeaders)
            ->addXKind(Kinds::userAdmin())
            ->addXKindId($requester->getId());

        $params = ConsentParams::newParams()
            ->withHeader($header)
            ->withClientId($client->getId());

        return $this->consentGateway->getConsentClient($params);
    }

    function createNewVersionConsentClient(Client $client, Users $requester, array $conditionsId, $traceHeaders = [])
    {
        $header = Header::build()
			->addTraceHeaders($traceHeaders)
            ->addXKind(Kinds::userAdmin())
            ->addXKindId($requester->getId());
        $body = Body::build()
            ->addClientId($client->getId())
            ->setListConditionsIds($conditionsId);
        $params = ConsentParams::newParams()
            ->withHeader($header)
            ->withBody($body);
        return $this->consentGateway->postConsentClient($params);
    }

    public function deleteConsentClient(Client $client, Users $requester, $consentId, $traceHeaders = [])
    {
        $header = Header::build()
            ->addTraceHeaders($traceHeaders)
            ->addXKind(Kinds::userAdmin())
            ->addXKindId($requester->getId());
        $params = ConsentParams::newParams()
            ->withConsentId($consentId)
            ->withHeader($header);
        $this->consentGateway->deleteConsentClient($params);
    }
}