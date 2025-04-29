<?php

namespace Wideti\ApiBundle\Controller;

use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Wideti\DomainBundle\Exception\ClientNotFoundException;
use Wideti\DomainBundle\Dto\ClientStatusReason;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\ClientHelper;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Helpers\Superlogica\ParseHookSuperlogicaHelper;
use Wideti\DomainBundle\Service\AccessPoints\AccessPointsService;
use Wideti\DomainBundle\Service\Cache\CacheService;
use Wideti\DomainBundle\Service\Client\ClientService;
use Wideti\DomainBundle\Service\ClientLogs\ClientLogServiceImp;
use Wideti\DomainBundle\Service\Erp\ErpServiceImp;
use Wideti\DomainBundle\Service\Client\ClientStatusService;
use Wideti\DomainBundle\Service\Client\Dto\ClientStatusDto;
use Wideti\DomainBundle\Service\Jaeger\TracerHeaders;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;

use Zend\Json\Json;


class ClientsController implements ApiResource
{
    use EntityManagerAware;

    /**
     * @var ClientHelper
     */
    private $clientHelper;
    /**
     * @var ClientService
     */
    private $clientService;
    /**
     * @var FrontendControllerHelper
     */
    private $controllerHelper;
    /**
     * @var ClientStatusService
     */
    private $clientStatusService;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var $clientLogServiceImp
     */
    private $clientLogServiceImp;

    /**
     * @var $erpSerivceImp
     */
    private $erpSerivceImp;

    /**
     * @var AccessPointsService $apService
     */
    private $apService;

    /**
     * @var string $apValidationToken
     */
    private $apValidationToken;
//    /**
//     * @var CacheService $cacheService
//     */
//    private $cacheService;


    /**
     * ClientsController constructor.
     * @param ClientHelper $clientHelper
     * @param ClientService $clientService
     * @param FrontendControllerHelper $controllerHelper
     * @param $clientLogServiceImp
     * @param $erpSerivceImp
     * @param ClientStatusService $clientStatusService
     * @param Logger $logger
     * @param AccessPointsService $apService
     * @param string $apValidationToken
//     * @param CacheService $cacheService
     */
    public function __construct(
        ClientHelper $clientHelper,
        ClientService $clientService,
        FrontendControllerHelper $controllerHelper,
        ClientLogServiceImp $clientLogServiceImp,
        ErpServiceImp $erpSerivceImp,
        ClientStatusService $clientStatusService,
        Logger $logger,
        AccessPointsService $apService,
        $apValidationToken
//        CacheService $cacheService
    ) {
        $this->clientHelper = $clientHelper;
        $this->clientService = $clientService;
        $this->controllerHelper = $controllerHelper;
        $this->clientLogServiceImp = $clientLogServiceImp;
        $this->erpSerivceImp = $erpSerivceImp;
        $this->clientStatusService = $clientStatusService;
        $this->logger = $logger;
        $this->apService = $apService;
        $this->apValidationToken = $apValidationToken;
//        $this->cacheService = $cacheService;
    }

    /**
     * @param $domain
     * @return JsonResponse
     * @throws \Doctrine\ORM\ORMException
     */
	public function isDomainAvailableAction($domain)
	{
        if (strpos($domain, 'mambowifi')) {
            $domain = explode(".mambowifi.com", $domain);
        } else {
            $domain = explode(".wspot.com.br", $domain);
        }

		$domain = $domain[0];

		if (!ClientHelper::domainIsValid($domain)) {
			return new JsonResponse(['available' => false]);
		}

		$client = $this->em
			->getRepository("DomainBundle:Client")
			->findByDomain($domain);

		if ($client || $this->clientHelper->checkIfIsReservedDomain($domain)) {
			return new JsonResponse(['available' => false], 409);
		}

		return new JsonResponse(['available' => true], 200);
	}

	/**
	 * @param $document
	 * @return JsonResponse
	 */
	public function isDocumentAvailableAction($document)
	{
		$client = $this->em
			->getRepository("DomainBundle:Client")
			->findByDocument($document);

		if ($client) {
			return new JsonResponse(['available' => false], 409);
		}

		return new JsonResponse(['available' => true], 200);
	}

    public function trialAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        return $this->clientService->trial($data);
    }

    public function purchaseAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $traceHeaders = TracerHeaders::from($request);
        return $this->clientService->purchase($data, $traceHeaders);
    }

    public function vinculateErpClientAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        return $this->clientService->vinculateerpclient($data);
    }

    public function changeStatusAction(Request $request)
    {
        try {
            $data = json_decode($request->getContent(), true);
            $cliente = $this->clientService->getClient($data);
            $this->clientService->changeStatus($data, $cliente);
        }catch (\Exception $ex){
            $this->logger->addCritical("Falha ao trocar o status do cliente {$data['domain']}: {$ex->getMessage()}");
            return new JsonResponse([
                "status"  => 500,
                "message" => "Falha ao trocar o status do cliente{$data['domain']}"
            ], 500);
        }

        return new JsonResponse([
            "status"  => 200,
            "message" => "Status do cliente {$data['domain']} alterado com sucesso"
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getClientInfoByHash(Request $request)
    {
        $hash = $request->get('hash');
        $client = $this->clientService->getClientInformationByHash($hash);
        if (empty($client)) {
            return new JsonResponse(null, 404);
        }
        $persistentCollection = $client[0]->getUsers();
        $user = $persistentCollection[0];
        return new JsonResponse(
            [
                'domain'        => $client[0]->getDomain(),
                'erpId'         => $client[0]->getErpId(),
                'admin'         => ['fullName'  => $user->getNome(),
                                    'email'     => $user->getUsername()],
                'company'       => ['name'      => $client[0]->getCompany()],

            ]
            , 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function getSettledChargesAction(Request $request)
    {
        $requestContent = json_decode($request->getContent(), true);
        if (empty($requestContent)) {
            return new JsonResponse(['message' => 'Empty request body'], 400);
        }
        $requestContent = $requestContent['data'][0];
        $erpClientId = $requestContent['id_sacado_sac'];
        $client = $this->clientService->getClientByErpId($erpClientId);
        $clientId = $client->getId();
        $erpClientData = $this->erpSerivceImp->getClientErpDataById($erpClientId);

        if (isset($requestContent['st_observacaoexterna_recb']) && $requestContent['st_observacaoexterna_recb'] == "Primeiro pagamento") {
            $status = $this->setFirstPaymentChargeSettledStatus($clientId);

            try {
                $this->clientStatusService->changeStatus($status);
            } catch (ClientNotFoundException $ex) {
                $this->logger->addCritical("Client ERP_ID {$client} not found on Api::ClientController::getSettledChargesAction, Exception: {$ex->getMessage()}");
            } catch (\Exception $e) {
                $this->logger->addCritical("Can't change client status to block in Api::ClientController::getSettledChargesAction, Exception: {$e->getMessage()}");
                return new JsonResponse(['status' => 500, 'message' => $e->getMessage()], 500);
            }

            $action = "Status do cliente alterado de POC para Ativo";
            $this->clientLogServiceImp->logClientSettlementCharge($client, $action);
            return new JsonResponse(['status' => '200'], 200);
        }

        if (isset($erpClientData['dt_congelamento_sac']) && !empty($erpClientData['dt_congelamento_sac'])) {
            $this->erpSerivceImp->unfreezeClientById($erpClientId);
            $status = $this->setUnfreezedStatus($clientId);

            try {
                $this->clientStatusService->changeStatus($status);
            } catch (ClientNotFoundException $ex) {
                $this->logger->addCritical("Client ERP_ID {$client} not found on Api::ClientController::getSettledChargesAction, Exception: {$ex->getMessage()}");
            } catch (\Exception $e) {
                $this->logger->addCritical("Can't change client status to block in Api::ClientController::getSettledChargesAction, Exception: {$e->getMessage()}");
                return new JsonResponse(['status' => 500, 'message' => $e->getMessage()], 500);
            }

            $action = "Status do cliente alterado de Bloqueado para Ativo";
            $this->clientLogServiceImp->logClientSettlementCharge($client, $action);
            return new JsonResponse(['status' => '200'], 200);
        }

        return new JsonResponse(['status' => 'ok'], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function syncDataAction(Request $request)
    {
        $requestContent = json_decode($request->getContent(), true);

        if (empty($requestContent) || !array_key_exists('data', $requestContent)) {
            return new JsonResponse(['message' => 'Empty request body'], 400);
        }

        $requestContent = $requestContent['data'];
        $erpClientId = $requestContent['id_sacado_sac'];
        $client = $this->clientService->getClientByErpId($erpClientId);

        if (!$client) {
            $this->logger->addWarning("Can't sync ERP to Mambo WiFi client data Api::ClientController::syncDataAction, Exception: Client not found", [
                'content' => $requestContent,
                'erpId' => $erpClientId
            ]);
            return new JsonResponse(['status' => '200'], 200);
        }

        try {
            $this->clientService->syncData($client, $requestContent);
        } catch (\Exception $e) {
            $this->logger->addWarning("Can't sync ERP to MamboWiFi client data Api::ClientController::syncDataAction, Exception: {$e->getMessage()}", [
                'content' => $requestContent,
                'erpId' => $erpClientId
            ]);
            return new JsonResponse(['status' => '200'], 200);
        }

        return new JsonResponse(['status' => '200'], 200);
    }

    /**
     * @param $clientId
     * @return ClientStatusDto
     */
    private function setUnfreezedStatus($clientId) {
        $status = new ClientStatusDto();
        $status->setAuthor('Superlogica integração')
            ->setClientId($clientId)
            ->setStatusReason(ClientStatusReason::UNFREEZE_CLIENT)
            ->setHttpMethod('put')
            ->setUrlOrigin('/clients/settled-charges')
            ->setNewStatus(Client::STATUS_ACTIVE);
        return $status;
    }

    /**
     * @param $clientId
     * @return ClientStatusDto
     */
    private function setFirstPaymentChargeSettledStatus($clientId) {
        $status = new ClientStatusDto();
        $status->setAuthor('Superlogica integração')
            ->setClientId($clientId)
            ->setStatusReason(ClientStatusReason::SETTLED_CHARGES)
            ->setHttpMethod('get')
            ->setUrlOrigin('/clients/settled-charges')
            ->setNewStatus(Client::STATUS_ACTIVE);
        return $status;
    }

    public function erpChangeStatusAction(Request $request)
    {
        $requestContent = json_decode($request->getContent(), true);

        if (empty($requestContent)) {
            return new JsonResponse(['message' => 'Empty request body'], 400);
        }

        try {
            $erpClients = ParseHookSuperlogicaHelper::parseClientStatusHook($requestContent);
        } catch (\Exception $e) {
            $this->logger->addCritical("Can't parse client info in Api::ClientController::ErpChangeStatusAction, Exception: {$e->getMessage()}");
            return new JsonResponse(['status' => 500], 500);
        }

        foreach ($erpClients as $erpClient) {
            if ($erpClient->getStatus() !== ParseHookSuperlogicaHelper::CLIENT_STATUS_CONGELADO
                || !$erpClient->isHasFinancialPending()) {
                continue;
            }

            $status = new ClientStatusDto();
            $status
                ->setAuthor('Superlogica Webhook')
                ->setErpId($erpClient->getErpId())
                ->setStatusReason(ClientStatusReason::FINANCIAL_PENDING)
                ->setHttpMethod($request->getMethod())
                ->setUrlOrigin($request->getUri())
                ->setNewStatus(Client::STATUS_BLOCKED);

            try {
                $this->clientStatusService->changeStatus($status);
            } catch (ClientNotFoundException $ex) {
                $this->logger->addCritical("Client ERP_ID {$erpClient->getErpId()} not found on Api::ClientController::ErpChangeStatusAction, Exception: {$ex->getMessage()}");
                continue;
            } catch (\Exception $e) {
                $this->logger->addCritical("Can't change client status to block in Api::ClientController::ErpChangeStatusAction, Exception: {$e->getMessage()}");
                return new JsonResponse(['status' => 500, 'message' => $e->getMessage()], 500);
            }
        }

        return new JsonResponse(['status' => 200]);
    }

    public function validateClientAp(Request $request)
    {
        $authorization = $request->headers->get('Authorization');

        if (is_null($authorization) || $authorization !== $this->apValidationToken) {
            return new JsonResponse(['status'=> 401], 401);
        }

        $clientId = $request->get('id');
        $apIdentifier = $request->get('identifier');

        $cacheKey = "ap:{$clientId}:{$apIdentifier}";

//        try {
//            $cacheResult = $this->cacheService->get($cacheKey);
//
//            if ($cacheResult) {
//                return new JsonResponse(['status: in cache' => 200], 200);
//            }
//        }catch (\Exception $e) {
//            $this->logger->addWarning("It was not possible to get ap in cache:\n". $e->getMessage());
//        }

        $ap = $this->apService->findByClientAndIdentifier($clientId, $apIdentifier);

        if (is_null($ap)) {
            return new JsonResponse(['status' => 404], 404);
        }

        if (!$ap->isRequestVerified() || !$ap->isRadiusVerified()) {
            try {
                $this->apService->validateApOnFirstAccess($ap);
            } catch (\Exception $e) {
                $this->logger
                    ->addCritical("Problemas ao marcar AP como verificada: {$e->getMessage()}");

                return new JsonResponse([
                    'status' => 500,
                    'context' => 'api_validate_ap',
                    'error_message' => $e->getMessage()],
                    500);
            }
        }

//        try {
//            $this->cacheService->set($cacheKey, true,86400, false, false);
//        }catch (\Exception $e) {
//            $this->logger->addWarning("It was not possible to save ap in cache:\n". $e->getMessage());
//        }

        return new JsonResponse(['status' => 200], 200);

    }

	public function getResourceName()
	{
		return 'clients';
	}
}


