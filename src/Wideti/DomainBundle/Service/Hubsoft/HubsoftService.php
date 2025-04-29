<?php

namespace Wideti\DomainBundle\Service\Hubsoft;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client as GuzzleClient;


class HubsoftService
{
    use EntityManagerAware;
    use TwigAware;
    use SecurityAware;
    use FlashMessageAware;
    use ModuleAware;
    use LoggerAware;


    private $guzzleClient;

    /**
     * HubsoftService constructor.
     */
    public function __construct() {
        $this->baseUriSandbox = 'https://api.demo.hubsoft.com.br';
        $this->guzzleClientConfig = [
            'defaults' => [
                'exceptions' => false,
            ],
            'headers' => ['Content-Type' => 'application/json']
        ];
    }

    public function testCredentials($client) {
        try {
            $host = $this->getModuleConfigurationValue($client, 'hubsoft_host');
            $this->guzzleClientConfig['base_uri'] = $host;

            $this->guzzleClient = new GuzzleClient($this->guzzleClientConfig);

            $responseToken = $this->getToken($client);
            return array_key_exists('access_token', $responseToken);
        } catch (\Exception $ex){
            $this->logger->addCritical($ex->getMessage());
            return false;
        } 
    }

    public function getIdOrigins($client) {
        $host = $this->getModuleConfigurationValue($client, 'hubsoft_host');
        $this->guzzleClientConfig['base_uri'] = $host;

        $this->guzzleClient = new GuzzleClient($this->guzzleClientConfig);

        $responseToken = $this->getToken($client);
        if (array_key_exists('access_token', $responseToken)) {
            $token = $responseToken['access_token'];
            $response = $this->getOrigins($token);
            return $response;
        } else {
            $this->logger->addCritical('falha ao obter token hubsoft');
            return [];
        }
    }

    private function getOrigins($token) {
        try {
            $response = $this->guzzleClient
                ->get("/api/v1/integracao/configuracao/origem_cliente", [
                    "headers" => ['Authorization' => 'Bearer ' . $token]
                ]
            );
            $jsonString = $response->getBody()->getContents();
            return json_decode($jsonString, true);
        }catch (RequestException $ex){
            $this->logger->addCritical($ex->getMessage());
            return [];
        }
    }

    public function getIdServices($client) {
        $host = $this->getModuleConfigurationValue($client, 'hubsoft_host');
        $this->guzzleClientConfig['base_uri'] = $host;

        $this->guzzleClient = new GuzzleClient($this->guzzleClientConfig);

        $responseToken = $this->getToken($client);
        if (array_key_exists('access_token', $responseToken)) {
            $token = $responseToken['access_token'];
            $response = $this->getServices($token);
            return $response;
        } else {
            $this->logger->addCritical('falha ao obter token hubsoft');
            return [];
        }
    }



    private function getServices($token) {
        try {
            $response = $this->guzzleClient
                ->get("/api/v1/integracao/configuracao/servico", [
                    "headers" => ['Authorization' => 'Bearer ' . $token]
                ]
            );
            $jsonString = $response->getBody()->getContents();
            return json_decode($jsonString, true);
        }catch (RequestException $ex){
            $this->logger->addCritical($ex->getMessage());
            return [];
        }
    }

    public function authAction($userCredentials)
    {
        $client = $this->getLoggedClient();

        $clientHubsoftModule = $this->moduleService->modulePermission('hubsoft_integration', $client);
        if (!$clientHubsoftModule) {
            return false;
        }

        $hubsoftIntegrationIsActive = $this->moduleService->checkModuleIsActive('hubsoft_integration', $client);

        if (!$hubsoftIntegrationIsActive) {
            return false;
        }
        
        $enableHubsoftAuth = $this->getModuleConfigurationValue($client, 'enable_hubsoft_authentication');
        if (!$enableHubsoftAuth) {
            return false;
        }

        $host = $this->getModuleConfigurationValue($client, 'hubsoft_host');
        $this->guzzleClientConfig['base_uri'] = $host;

        $this->guzzleClient = new GuzzleClient($this->guzzleClientConfig);

        $responseToken = $this->getToken($client);

        if (array_key_exists('access_token', $responseToken)) {
            $token = $responseToken['access_token'];
        } else {
            $this->logger->addCritical('falha ao obter token hubsoft');
            return ;
        }
        $username = $userCredentials["username"];
        $password = $userCredentials["password"];
        $authResponse = $this->auth($token, $username, $password);
        return $authResponse;
    }

    private function getModuleConfigurationValue($client, $key) {
        $moduleConfiguration = $this->em
        ->getRepository('DomainBundle:ModuleConfigurationValue')
        ->findByModuleConfigurationKey($client, $key);
        if ($moduleConfiguration) {
            return $moduleConfiguration->getValue();
        }
        return "";
    }

    private function getModuleConfigurationValues($client) {
        $moduleConfiguration = $this->em
        ->getRepository('DomainBundle:ModuleConfigurationValue')
        ->findModuleConfigurationsByModuleKey($client->getId(), 'hubsoft_integration');
        return $moduleConfiguration;
    }

    private function getToken($client) {
        $clientSecret = $this->getModuleConfigurationValue($client, 'hubsoft_client_secret');
        $clientId = $this->getModuleConfigurationValue($client, 'hubsoft_client_id');
        $username = $this->getModuleConfigurationValue($client, 'hubsoft_username');
        $password = $this->getModuleConfigurationValue($client, 'hubsoft_password');
        $body = [
            "grant_type" => "password",
            "client_id" => $clientId,
            "client_secret" => $clientSecret,
            "username" => $username,
            "password" => $password
        ];
        try {
            $response = $this->guzzleClient
                ->post("/oauth/token", [
                  "body" => json_encode($body)
                ]
            );
            $jsonString = $response->getBody()->getContents();
            return json_decode($jsonString, true);
        }catch (\Exception $ex){
            $this->logger->addCritical($ex->getMessage());
            return [];
        }
    }

    public function isActive($client) {
        $clientHubsoftModule = $this->moduleService->modulePermission('hubsoft_integration', $client);
        $hubsoftIntegrationIsActive = $this->getModuleConfigurationValue($client, 'enable_hubsoft_integration');
        return $clientHubsoftModule && $hubsoftIntegrationIsActive;
    }

    public function shouldAuthClient($client) {
        $enableHubsoftAuth = $this->getModuleConfigurationValue($client, 'enable_hubsoft_authentication');
        return $enableHubsoftAuth;
    }

    public function shouldSendProspect($client) {
        $enableHubsoftProspect = $this->getModuleConfigurationValue($client, 'enable_hubsoft_prospecting');
        return $enableHubsoftProspect;
    }

    public function getAuthButtonText($client) {
        return $this->getModuleConfigurationValue($client, 'hubsoft_auth_button');
    }

    public function getButtonColor($client) {
        return $this->getModuleConfigurationValue($client, 'hubsoft_button_color');
    }

    public function getTitleText($client) {
        return $this->getModuleConfigurationValue($client, 'hubsoft_title_text');
    }

    public function getSubtitleText($client) {
        return $this->getModuleConfigurationValue($client, 'hubsoft_subtitle_text');
    }

    public function getAuthenticatedClientGroup($client) { 
        return $this->getModuleConfigurationValue($client, 'hubsoft_client_group');
    }

    public function prospectAction($guest) {
        $client = $this->getLoggedClient();
        $clientHubsoftModule = $this->moduleService->modulePermission('hubsoft_integration', $client);
        if (!$clientHubsoftModule) {
            return false;
        }

        $hubsoftIntegrationIsActive = $this->moduleService->checkModuleIsActive('hubsoft_integration', $client);

        if (!$hubsoftIntegrationIsActive) {
            return false;
        }

        $hubsoftProspectIsActive = $this->getModuleConfigurationValue($client, 'enable_hubsoft_prospecting');

        if (!$hubsoftProspectIsActive) {
            return false;
        }
        $accessPoint = $guest->getRegistrationMacAddress();

        $host = $this->getModuleConfigurationValue($client, 'hubsoft_host');
        $this->guzzleClientConfig['base_uri'] = $host;

        $this->guzzleClient = new GuzzleClient($this->guzzleClientConfig);

        $idService = $this->getModuleConfigurationValue($client, 'hubsoft_id_service');
        $idOrigin = $this->getModuleConfigurationValue($client, 'hubsoft_id_origin');
        $idCrm = $this->getModuleConfigurationValue($client, 'hubsoft_id_crm');
        $data = [
            "servico" => [
                "id_servico" => $idService,
                "valor" => 0
            ],
            "id_crm" =>  $idCrm,
            "id_origem_cliente" =>  $idOrigin,
            "tipo_pessoa" => "pf",
            "observacao" => "Ponto de Acesso: " . $accessPoint
        ];
        $fieldNames = [
            "zip_code" => "cep",
            "document" => "cpf_cnpj",
            "phone" => "telefone",
            "name" => "nome_razaosocial",
            "district" => "bairro",
            "address" => "endereco",
            "number" => "numero",
	        "email" =>  "email"
        ];
        foreach ($guest->getProperties() as $key => $value) {
            if (array_key_exists($key, $fieldNames)) {
                $data[$fieldNames[$key]] = $value;
            }
        }
        $responseToken = $this->getToken($client);

        if (array_key_exists('access_token', $responseToken)) {
            $token = $responseToken['access_token'];
        } else {
            $this->logger->addCritical('falha ao obter token hubsoft');
            return false;
        }
        $prospectResponse = $this->sendProspect($token, $data);
        if ($prospectResponse && $prospectResponse['status'] == "error") {
            $this->logger->addCritical('Erro ao enviar prospecto: ' . $prospectResponse['msg'] . " - " . json_encode($prospectResponse['errors'], JSON_UNESCAPED_UNICODE));
        }
        return true;
    }

    private function sendProspect($token, $data) {
        try {
            $response = $this->guzzleClient
                ->post("/api/v1/integracao/prospecto", [
                    "body" => json_encode($data),
                    "headers" => ['Authorization' => 'Bearer ' . $token]
                ]
            );
            $jsonString = $response->getBody()->getContents();
            return json_decode($jsonString, true);
        }catch (RequestException $ex){
            $this->logger->addCritical($ex->getMessage());
        }
    }

    private function auth($token, $username, $password) {
        $body = [
          "usuario"    => $username,
          "senha"    => $password
        ];
        try {
            $response = $this->guzzleClient
                ->post("/api/v1/integracao/cliente/autenticacao/", [
                    "body" => json_encode($body),
                    "headers" => ['Authorization' => 'Bearer ' . $token]
                ]
            );
            $jsonString = $response->getBody()->getContents();
            return json_decode($jsonString, true);
        }catch (RequestException $ex){
            $this->logger->addCritical($ex->getMessage());
        }
    }
}