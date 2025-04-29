<?php

namespace Wideti\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\FirstConfig\Dto\FieldFirstConfigDTO;
use Wideti\DomainBundle\Service\FirstConfig\Dto\FirstConfigurationParameters;
use Wideti\DomainBundle\Service\FirstConfig\FirstConfigService;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\DomainBundle\Service\Client\ClientService;

class FirstConfigController
{
    use TwigAware;
    use SessionAware;

    /**
     * @var FirstConfigService
     */
    private $firstConfigService;
    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var ClientService
     */
    private $clientService;

    public function __construct(
            FirstConfigService $firstConfigService,
            AdminControllerHelper $controllerHelper,
            ConfigurationService $configurationService,
            ClientService $clientService
    ) {
        $this->firstConfigService = $firstConfigService;
        $this->controllerHelper = $controllerHelper;
        $this->configurationService = $configurationService;
        $this->clientService = $clientService;
    }

    public function index()
    {
        $client = $this->session->get('wspotClient');
        $configMap = $this->configurationService->getDefaultConfiguration($client);
        $captiveType = $client->getAuthenticationType();
        $this->configurationService->setOnSession($this->configurationService->getCacheKey('admin'), $configMap);

        
        return $this->render('@Admin/FirstConfig/index.html.twig', [
            'captiveType' => $captiveType,        ]);
    }

    public function ajaxLoadTemplateFields()
    {
        $client = $this->getLoggedClient();
        $fields = $this->firstConfigService->getTemplateFields($client);
        return new JsonResponse($fields);
    }

    public function ajaxSaveFields(Request $request)
    {
        $clientSession = $this->getLoggedClient();
        $client = $this->clientService->getClientById($clientSession->getId());

        $fieldsArray = json_decode($request->getContent(), true);

        $errorResponse = $this->saveFieldErrorHandler($fieldsArray);
        if ($errorResponse) return $errorResponse;

        $configParameters = $this->mappingParameters($fieldsArray);
        $response = $this->firstConfigService->processConfigParameters($configParameters, $client);

        return new JsonResponse($response);
    }

    /**
     * @param $fieldsArray
     * @return JsonResponse
     */
    private function saveFieldErrorHandler($fieldsArray)
    {
        if (!$fieldsArray) {
            return new JsonResponse(
                ['message' => "Você não selecionou os campos para cadastro ou o campo para login"], 400
            );
        }

        if (!isset($fieldsArray['signUpFields']) || empty($fieldsArray['signUpFields'])) {
            return new JsonResponse(
                ['message' => "Você não selecionou os campos para cadastro."], 400
            );
        }

        if (!isset($fieldsArray['signInField']) || empty($fieldsArray['signInField'])) {
            return new JsonResponse(
                ['message' => "Você não selecionou os campos para login."], 400
            );
        }

        return null;
    }

    /**
     * @param $fieldsArray
     * @return FirstConfigurationParameters
     */
    private function mappingParameters($fieldsArray)
    {
        $parameters = new FirstConfigurationParameters();
        $signUpFields = [];

        foreach ($fieldsArray['signUpFields'] as $field) {
            $signUpFields[] = FieldFirstConfigDTO::buildFromArray($field);
        }

        $parameters->setSignUpFields($signUpFields);
        $parameters->setSignInField(FieldFirstConfigDTO::buildFromArray($fieldsArray['signInField']));

        return $parameters;
    }
}
