<?php

namespace Wideti\DomainBundle\Service\Ixc;

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


class IxcService
{
    use EntityManagerAware;
    use TwigAware;
    use SecurityAware;
    use FlashMessageAware;
    use ModuleAware;
    use LoggerAware;


    private $guzzleClient;

    /**
     * IxcService constructor.
     */
    public function __construct() {
        $this->baseUriSandbox = 'https://demo.ixcsoft.com.br/';
        $this->guzzleClientConfig = [
            'defaults' => [
                'exceptions' => false,
            ],
            'headers' => ['Content-Type' => 'application/json']
        ];
    }

    public function testCredentials($client) {
        try {
            $host = $this->getModuleConfigurationValue($client, 'Ixc_host');
            $this->guzzleClientConfig['base_uri'] = $host;
            $this->guzzleClientConfig['timeout'] = 10.0;  // Timeout em segundos
            $this->guzzleClient = new GuzzleClient($this->guzzleClientConfig);
            $Ixc_client_secret = $this->getModuleConfigurationValue($client, 'Ixc_client_secret');

            $authToken = base64_encode($Ixc_client_secret);


            // Faz a requisição ao endpoint de contato com o token fornecido
            $response = $this->guzzleClient->request('GET', 'webservice/v1/contato', [
                'headers' => [
                'Authorization' => 'Basic ' . $authToken,
                    'ixcsoft' => 'listar'  // Cabeçalho adicional exigido pelo IXC Soft
                ]
            ]);

            return $response->getStatusCode() === 200;

        } catch (\Exception $ex){
            $this->logger->addCritical($ex->getMessage());
            return false;
        } 
    }

    public function authAction($userCredentials)
    {
        $client = $this->getLoggedClient();

        $clientIxcModule = $this->moduleService->modulePermission('Ixc_integration', $client);
        if (!$clientIxcModule) {
            return [
                'status' => 'error',
                'message' => 'Módulo de integração IXC não permitido.'
            ];
        }

        $IxcIntegrationIsActive = $this->moduleService->checkModuleIsActive('Ixc_integration', $client);

        if (!$IxcIntegrationIsActive) {
            return [
                'status' => 'error',
                'message' => 'Módulo de integração IXC não está ativo.'
            ];
        }
        
        $enableIxcAuth = $this->getModuleConfigurationValue($client, 'enable_Ixc_authentication');
        if (!$enableIxcAuth) {
            return [
                'status' => 'error',
                'message' => 'Autenticação IXC não está habilitada.'
            ];
        }

        $host = $this->getModuleConfigurationValue($client, 'Ixc_host');
        $this->guzzleClientConfig['base_uri'] = $host;

        $this->guzzleClient = new GuzzleClient($this->guzzleClientConfig);


        $username = $userCredentials["username"];
        $password = $userCredentials["password"];
        $authResponse = $this->auth( $client ,$username, $password);

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
        ->findModuleConfigurationsByModuleKey($client->getId(), 'Ixc_integration');
        return $moduleConfiguration;
    }


    public function isActive($client) {
        $clientIxcModule = $this->moduleService->modulePermission('Ixc_integration', $client);
        $IxcIntegrationIsActive = $this->getModuleConfigurationValue($client, 'enable_Ixc_integration');
        return $clientIxcModule && $IxcIntegrationIsActive;
    }

    public function shouldAuthClient($client) {
        $enableIxcAuth = $this->getModuleConfigurationValue($client, 'enable_Ixc_authentication');
        return $enableIxcAuth;
    }

    public function shouldSendProspect($client) {
        $enableIxcProspect = $this->getModuleConfigurationValue($client, 'enable_Ixc_prospecting');
        return $enableIxcProspect;
    }

    public function getAuthButtonText($client) {
        return $this->getModuleConfigurationValue($client, 'Ixc_auth_button');
    }

    public function getButtonColor($client) {
        return $this->getModuleConfigurationValue($client, 'Ixc_button_color');
    }

    public function getTitleText($client) {
        return $this->getModuleConfigurationValue($client, 'Ixc_title_text');
    }

    public function getSubtitleText($client) {
        return $this->getModuleConfigurationValue($client, 'Ixc_subtitle_text');
    }

    public function getAuthenticatedClientGroup($client) { 
        return $this->getModuleConfigurationValue($client, 'Ixc_client_group');
    }

    public function prospectAction($guest) {

        $client = $this->getLoggedClient();
        $clientIxcModule = $this->moduleService->modulePermission('Ixc_integration', $client);
        if (!$clientIxcModule) {
            return false;
        }

        $IxcIntegrationIsActive = $this->moduleService->checkModuleIsActive('Ixc_integration', $client);

        if (!$IxcIntegrationIsActive) {
            return false;
        }

        $IxcProspectIsActive = $this->getModuleConfigurationValue($client, 'enable_Ixc_prospecting');

        if (!$IxcProspectIsActive) {
            return false;
        }
        $accessPoint = $guest->getRegistrationMacAddress();
        $guestEmail = (array_key_exists('email', $guest->getProperties())) ? $guest->getProperties()['email'] : null;

        $host = $this->getModuleConfigurationValue($client, 'Ixc_host');
        $this->guzzleClientConfig['base_uri'] = $host;

        $this->guzzleClient = new GuzzleClient($this->guzzleClientConfig);

        $lead_data = [
            "nome" => $guestEmail,
            "email" => $guestEmail,
            "data_cadastro" => date("Y/m/d"),
            "tipo_pessoa"   => "F",
            "lead"          => "S",
            "ativo"         => "S",
            "origem_source" => "Mambo WiFi",
            "obs"           => "Cadastrado por " .  $client->getCompany() . " via Mambo Wifi no Ponto de Acesso: " . $accessPoint,
        ];
        $propertyKeys = [
            "name", "phone", "document", "data_nascimento", "birthday", 
            "birthday_month", "gender", "age", "vehicle", "km", "license_plate", 
            "city", "district", "zip_code", "occupation", "quem_indicou", "sport", 
            "team", "uf", "marital_status", "last_name", "company", "position", 
            "mobile", "upn", "custom_oldmambo_document", "custom_oldmambo_id_document", 
            "dialCodeMobile", "dialCodePhone"
        ];

        // Iterate over each property key and add to $data if it exists
        foreach ($propertyKeys as $key) {
            if (array_key_exists($key, $guest->getProperties())) {
                $guest_data[$key] = $guest->getProperties()[$key];
            }
        }
        $lead_data["nome"] = isset($guest_data["name"]) ? $guest_data["name"] : $guestEmail;
        $lead_data["fone_residencial"] = isset($guest_data["phone"]) ? $guest_data["phone"] : null;
        $lead_data["fone_celular"] = isset($guest_data["mobile"]) ? $guest_data["mobile"] : null;
        $lead_data["cnpj_cpf"] = isset($guest_data["document"]) ? $guest_data["document"] : null;
        $lead_data["cidade"] = isset($guest_data["city"]) ? $guest_data["city"] : null;
        $lead_data["cep"] = isset($guest_data["zip_code"]) ? $guest_data["zip_code"] : null;
        $lead_data["bairro"] = isset($guest_data["district"]) ? $guest_data["district"] : null;
        $lead_data["endereco"] = isset($guest_data["endereco"]) ? $guest_data["endereco"] : null;
        $lead_data["numero"] = isset($guest_data["numero"]) ? $guest_data["numero"] : null;
        $lead_data["profissao"] = isset($guest_data["occupation"]) ? $guest_data["occupation"] : null;
        $lead_data["uf"] = isset($guest_data["uf"]) ? $guest_data["uf"] : null;




        $clientSecret = $this->getModuleConfigurationValue($client, 'Ixc_client_secret'); #token
        $host = $this->getModuleConfigurationValue($client, 'Ixc_host');

        $prospectResponse = $this->sendProspect($host,$clientSecret, $lead_data);
        if (!$prospectResponse) {
            $this->logger->addCritical('Erro ao enviar prospecto: ' . $prospectResponse['msg'] . " - " . json_encode($prospectResponse['errors'], JSON_UNESCAPED_UNICODE));
        }
        return true;
    }

    private function sendProspect($host,$clientSecret, $data) {

        try {
            // Preparar a URL do endpoint
            $url = "{$host}/webservice/v1/contato";

            // Preparar os cabeçalhos
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($clientSecret) // Ajuste aqui se o token não for em Base64
            ];


            // Enviar a solicitação POST
            $response = $this->guzzleClient->post($url, [
                'headers' => $headers,
                'body' => json_encode($data)
            ]);

            // // Processar a resposta
                $jsonString = $response->getBody()->getContents();
                $responseData = json_decode($jsonString, true);

                return $responseData;
            } catch (RequestException $ex) {
                // Registrar erro e retornar false ou uma mensagem
                $this->logger->addCritical('ERRO sendProspect: ' . $ex->getMessage());

                // Log da resposta de erro (caso tenha)
                if ($ex->hasResponse()) {
                    $errorResponse = $ex->getResponse()->getBody()->getContents();
                    $this->logger->addCritical('Resposta de erro da API: ' . $errorResponse);
                }

                return false;
            }

    }

    private function auth($client, $username, $password) {

        $host = $this->getModuleConfigurationValue($client, 'Ixc_host');
        $Ixc_client_secret = $this->getModuleConfigurationValue($client, 'Ixc_client_secret');
        $url = "{$host}/webservice/v1/cliente";
        $body = [
            'qtype' => 'cliente.hotsite_email',
            'query' => $username,
            'oper' => '=',
            'page' => '1',
            'rp' => '1',
            'sortname' => 'cliente.id',
            'sortorder' => 'asc',
            'grid_param' => json_encode([["TB" => "cliente.senha", "OP" => "=", "P" => $password]])
        ];

        try {
            $response = $this->guzzleClient->get($url, [
                'headers' => [
                    'ixcsoft' => 'listar',
                    'Authorization' => 'Basic ' . base64_encode($Ixc_client_secret),
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($body)
            ]);

            $jsonString = $response->getBody();
            $data = json_decode($jsonString, true);

            if ($data['total'] == 0){
                return false;
            }
            foreach ($data['registros'][0] as $key => $value) {
                $user[$key] = $value;
            }

            return [
                'status' => 'success',
                'message' => 'Dados do cliente obtidos com sucesso.',
                'client_data' =>$user
            ];
            
        } catch (RequestException $ex) {
            $this->logger->addCritical('ERRO iXC auth'.$ex->getMessage());
            return [
                'status' => 'error',
                'message' => 'Erro na autenticação: '
            ];

        }


    }

}

