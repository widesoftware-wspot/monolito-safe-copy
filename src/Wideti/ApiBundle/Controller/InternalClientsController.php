<?php

namespace Wideti\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\WhiteLabel;
use Wideti\DomainBundle\Repository\ClientRepository;
use Wideti\DomainBundle\Service\Client\ClientServiceAware;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\DomainBundle\Service\WhiteLabel\WhiteLabelServiceImp;

class InternalClientsController implements ApiResource
{
    use EntityManagerAware;
    use ClientServiceAware;

    const RESOURCE_NAME = 'internal_clients';

    /**
     * @var WhiteLabel
     * ServiceImp $whiteLabelService
     */
    private $whiteLabelService;

    /**
     * ClientsController constructor.
     * @param WhiteLabelServiceImp $whiteLabelService

     */
    public function __construct(
        WhiteLabelServiceImp $whiteLabelService
    ) {
        $this->whiteLabelService = $whiteLabelService;
    }

    public function searchClient(Request $request)
    {
        $databaseName = $request->get("database_name");
        if (is_null($databaseName) || $databaseName == ""){
            return JsonResponse::create(['error' => "É necessário passar a query string 'database_name'."], 400);
        }
        /**
         * @var Client $client
         */
        $client = $this->getClientRepository()->findOneBy(['mongoDatabaseName'=>$databaseName]);

        if (is_null($client)){
            return JsonResponse::create(["msg" => "cliente não encontrado."], 404);
        }

        $modulesShortCodes = array();
        $userEmails = array();

        foreach ($client->getModules() as $module) {
            array_push($modulesShortCodes, $module->getShortCode());
        }

        foreach ($client->getUsers() as $user) {
            array_push($userEmails, $user->getUsername());
        }

        $clientArray = $client->toArray();
        $clientArray['modules'] = $modulesShortCodes;
        $clientArray['users'] = $userEmails;

        return JsonResponse::create($clientArray, 200);
    }

    /**
     * @return ClientRepository
     */
    private function getClientRepository()
    {
        return $this->em->getRepository(Client::class);
    }


    public function getResourceName()
    {
        return self::RESOURCE_NAME;
    }

    public function getTemplateData(Request $request)
    {
        $whiteLabel = $this->em->getRepository("DomainBundle:WhiteLabel")->findOneBy(['client'=>$request->get('id', null)]);
        if (is_null($whiteLabel)) {
            return JsonResponse::create(null, 404);
        }
        return JsonResponse::create($whiteLabel->toArray(), 200);
    }

    public function editAction(Request $request)
    {
        $body = json_decode($request->getContent(), true);

        $errorMsg = $this->verifyFields($body);
        if($errorMsg != null) {
            return JsonResponse::create($errorMsg, 409);
        }
        $client = $this->em->getRepository("DomainBundle:Client")->findOneBy(['id'=>$request->get('id', null)]);
        $modules = $this->em->getRepository("DomainBundle:Module")->findBy(['id' => $body["modules"]]);
        $client->setCompany($body["company"]);
        $client->setErpId($body["erp_id"]);
        $client->setZipCode($body["zipCode"]);
        $client->setAddress($body["address"]);
        $client->setAddressNumber($body["addressNumber"]);
        $client->setAddressComplement($body["addressComplement"]);
        $client->setDistrict($body["district"]);
        $client->setCity($body["city"]);
        $client->setState($body["state"]);
        $client->setSmsCost($body["smsCost"]);
        $client->setContractedAccessPoints($body["contractedAccessPoints"]);
        $client->setClosingDate($body["closingDate"]);
        $client->setStatus($body["status"]);
        $client->setApCheck($body["apCheck"]);
        if (isset($body["pocEndDate"]) && $body["pocEndDate"] !== "") {
            $client->setPocEndDate(new \DateTime($body["pocEndDate"]));
        }
        $plan = $this->em->getRepository("DomainBundle:Plan")->findOneBy(['id' => $body["plan_id"]]);
        $segment = $this->em->getRepository("DomainBundle:Segment")->findOneBy(['id' => $body["segment_id"]]);
        $client->setPlan($plan);
        $client->setSegment($segment);
        $client->setEmailSenderDefault($body['emailSenderDefault']);
        $client->setEnableMacAuthentication($body["enableMacAuthentication"]);
        foreach($client->getModules() as $storedModule) {
            $client->removeModule($storedModule);
        }
        foreach($modules as $module) {
            $client->addModule($module);
        }
        $this->clientService->update($client);
        return JsonResponse::create($client->toArray(), 200);
    }

    protected function verifyFields($body)
    {
        if($body["company"] == null || trim($body["company"]) == "" ) {
            return "Campo company é obrigatório";
        }
        if($body["erp_id"] == null || trim($body["erp_id"]) == "") {
            return "Campo erp_id é obrigatório";
        }
//        if($body["type"] == null || trim($body["type"]) == "" ) {
//            return "Campo type é obrigatório";
//        }
        if($body["contractedAccessPoints"] == null || trim($body["contractedAccessPoints"]) == "" ) {
            return "Campo contractedAccessPoints é obrigatório";
        }
        if($body["status"] == null || trim($body["status"]) == "" ) {
            return "Campo status é obrigatório";
        }
        if($body["apCheck"] !== false && $body["apCheck"] !== true) {
            return "Campo apCheck é obrigatório";
        }
        if($body["plan_id"] == null || trim($body["plan_id"]) == "" ) {
            return "Campo plan_id é obrigatório";
        }
        if($body["segment_id"] == null || trim($body["segment_id"]) == "" ) {
            return "Campo segment_id é obrigatório";
        }
        if($body["enableMacAuthentication"] == null || trim($body["enableMacAuthentication"]) == "" ) {
            return "Campo enableMacAuthentication é obrigatório";
        }
        return null;
    }

    public function syncWhiteLabelData(Request $request) {
        $requestContent = json_decode($request->getContent(), true);
        if (empty($requestContent)) {
            return new JsonResponse(['message' => 'Empty request body'], 400);
        }

        try {
            $whiteLabelIdentity = [
                'company_name'  => $requestContent['whitelabel']['company_name'],
                'panel_color'   => $requestContent['whitelabel']['panel_color'],
                'logotipo'      => $requestContent['whitelabel']['logotipo'],
                'signature'     => $requestContent['whitelabel']['signature']
            ];
            $clientIdsArray = $requestContent['client_ids'];
            $clientIds = '(';
            foreach ($clientIdsArray as $n) {
                if ($clientIds == '(') {
                    $clientIds = $clientIds . $n;
                } else {
                    $clientIds = $clientIds . ',' . $n;
                }
            }
            $clientIds = $clientIds . ')';
            $this->whiteLabelService->updateWhiteLabelsByClientIds($whiteLabelIdentity, $clientIds);

        } catch (ContextErrorException $ex) {
            $this->logger->addCritical("Erro: {$ex->getMessage()}");
            return new JsonResponse([
                'status' => 400,
                'context' => 'api_client_sync_white_label_data',
                'error_message' => 'Context error'],
                400);

        } catch (InvalidFieldNameException $es) {
            $this->logger->addCritical("Erro: {$es->getMessage()}");
            return new JsonResponse([
                'status' => 400,
                'context' => 'api_client_sync_white_label_data',
                'error_message' => 'Invalid field name'],
                400);

        } catch (\Exception $e) {
            $this->logger->addCritical("Erro: {$e->getMessage()}");
            return new JsonResponse([
                'status' => 500,
                'context' => 'api_client_sync_white_label_data',
                'error_message' => 'Erro ao ao atualizar spots'],
                500);
        }
        return new JsonResponse(null, 204);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateClientAction(Request $request)
    {
        $requestData = json_decode($request->getContent(), true);
        if ($requestData === null) {
            return new JsonResponse("Invalid JSON", 400);
        }
        $validationResult = $this->validaterequestData($requestData);
        $isValidBody = $validationResult[0];
        $errorMessage = $validationResult[1];
        if (!$isValidBody) {
            return new JsonResponse(["error" => $errorMessage], 400);
        }

        $clientId = $request->get('id', null);

        $client = $this->em->getRepository("DomainBundle:Client")->findOneBy(['id' => $clientId]);

        if (!$client) {
            return new JsonResponse(null, 404); // Client not found
        }

        try {
            return $this->handleUpdates($client,$requestData);
        } catch (Exception $e) {
            return new JsonResponse(null, 500);
        }
    }

    /**
     * @param $client
     * @param $value
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function handleUpdates(Client $client, array $requestData)
    {
        try {
            $client->setStatus($requestData['value']);
            if (isset($requestData['value']) && $requestData['value'] == 2) {
                $client->setPocEndDate(new \DateTime('+8 days'));
            }

            if ((isset($requestData['contracted_access_points'])) && ($requestData['contracted_access_points'] != '0' || $requestData['contracted_access_points'] != 0)){
                $client->setContractedAccessPoints($requestData['contracted_access_points']);
            }
            $this->clientService->update($client);
            return JsonResponse::create($client->toArray(), 200);
        } catch (\Doctrine\ORM\OptimisticLockException $e) {
            return new JsonResponse("Error updating client: " . $e->getMessage(), 500);
        } catch (\Exception $e) {
            return new JsonResponse("Error updating client", 500);
        }
    }

        /**
     * @param array $body
     * @return array, [$boolean,$errormessage]
     */
    private function validaterequestData(array $requestData)
    {
        if(!isset($requestData['field']) || !isset($requestData['value'])){
        return [false, "Field and value must be provided"];
        }

            return [true, null];
    }
}
