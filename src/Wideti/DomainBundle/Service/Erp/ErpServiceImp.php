<?php

namespace Wideti\DomainBundle\Service\Erp;

use Symfony\Bridge\Monolog\Logger;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Roles;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Service\Erp\Dto\ErpTokenDto;
use Wideti\DomainBundle\Service\HttpRequest\HttpRequestService;
use Wideti\DomainBundle\Service\Erp\Dto\ClientData;
use Wideti\DomainBundle\Service\Roles\RolesServiceImp;

class ErpServiceImp implements ErpService
{
    const ENDPOINT_CONTATOS = 'https://api.superlogica.net/v2/financeiro/contatos';
    const ENDPOINT_TOKEN    = 'https://api.superlogica.net/v2/financeiro/clientes/token';
    const ENDPOINT_CLIENTS  = 'https://api.superlogica.net/v2/financeiro/clientes';
    const ENDPOINT_CHARGES  = 'https://api.superlogica.net/v2/financeiro/cobranca';
    const ENDPOINT_PRODUCTS = 'https://api.superlogica.net/v2/financeiro/produtos';

    /**
     * @var
     */
    private $accessToken;
    /**
     * @var
     */
    private $appToken;
    /**
     * @var HttpRequestService
     */
    private $httpRequestService;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var RolesServiceImp
     */
    private $rolesService;

    /**
     * ErpServiceImp constructor.
     * @param $accessToken
     * @param $appToken
     * @param HttpRequestService $httpRequestService
     * @param Logger $logger
     * @param RolesServiceImp $rolesService
     */
    public function __construct(
        $accessToken, $appToken,
        HttpRequestService $httpRequestService,
        Logger $logger,
        RolesServiceImp $rolesService
    ) {
        $this->accessToken          = $accessToken;
        $this->appToken             = $appToken;
        $this->httpRequestService   = $httpRequestService;
        $this->logger               = $logger;
        $this->rolesService         = $rolesService;
    }

    /**
     * @param Users $user
     * @return mixed
     */
    public function addContact(Users $user)
    {
        if (!$user->getClient()->getErpId()) {
            return false;
        }

        $headers    = [
            'Content-type'  => 'application/json',
            'access_token'  => $this->accessToken,
            'app_token'     => $this->appToken
        ];

        $body       = [
            'ID_SACADO' => $user->getClient()->getErpId(),
            'ST_NOME'   => $user->getNome(),
            'EMAIL'     => $user->getUsername()
        ];

        $response   = $this
            ->httpRequestService
            ->post(self::ENDPOINT_CONTATOS, $headers, $body);

        $codes = [
            200, 206
        ];

        if (!in_array($response->getStatus(), $codes)) {
            // contratação automática tenta cadastrar mais de uma vez o mesmo contato, verificar fluxos de trial, purchase e updatePOCToClient
            // devido a isso logaremos como warning.
            if ($response->getContent()->msg == "Contato já cadastrado.") {
                $this->logger->addWarning(
                    "Fail to inser client contact on Superlogica - contact already exists:
                {$response->getStatus()} - {$response->getContent()->msg}",
                    [
                        'erp_id'        => $user->getClient()->getErpId(),
                        'domain'        => $user->getClient()->getDomain(),
                        'user_email'    => $user->getUsername()
                    ]
                );
            } else {
                $this->logger->addCritical(
                    "Fail to insert client contact on Superlogica:
                {$response->getStatus()} - {$response->getContent()->msg}",
                    [
                        'erp_id'        => $user->getClient()->getErpId(),
                        'domain'        => $user->getClient()->getDomain(),
                        'user_email'    => $user->getUsername()
                    ]
                );
            }
        }

        return $response->getContent();
    }

    /**
     * @param Users $user
     * @return mixed
     */
    public function removeContact(Users $user)
    {
        if (!$user->getClient()->getErpId()) {
            return false;
        }

        $headers    = [
            'Content-type'  => 'application/json',
            'access_token'  => $this->accessToken,
            'app_token'     => $this->appToken
        ];

        $body       = [
            'ID_SACADO' => $user->getClient()->getErpId(),
            'EMAIL'     => $user->getUsername(),
        ];

        $response   = $this
            ->httpRequestService
            ->post($this::ENDPOINT_CONTATOS . "/delete", $headers, $body);

        if ($response->getStatus() !== 200) {
            $this->logger->addError(
                "Fail to remove client contact on Superlogica: 
                {$response->getStatus()} - {$response->getContent()[0]->msg}",
                [
                    'erp_id'        => $user->getClient()->getErpId(),
                    'domain'        => $user->getClient()->getDomain(),
                    'user_email'    => $user->getUsername()
                ]
            );
        }

        return $response->getContent()[0];
    }

    /**
     * @param Users $user
     * @return ErpTokenDto
     */
    public function getToken(Users $user)
    {
        if (!is_null($user->getClient())) {
            if (!$user->getClient()->getErpId()) {
                return ErpTokenDto::error("Cliente sem cadastro ou vínculo no ERP.");
            }
        }


        if ($user->getFinancialManager() == 0) {
            return ErpTokenDto::error("O usuário não está cadastrado como Gestor financeiro.");
        }

        $headers    = [
            'Content-type'  => 'application/json',
            'access_token'  => $this->accessToken,
            'app_token'     => $this->appToken
        ];

        if ($this->rolesService->isRoleManagerUser($user)) {
            return ErpTokenDto::create("");
        };

        $url = $this::ENDPOINT_TOKEN . "?id={$user->getClient()->getErpId()}&email={$user->getUsername()}";

        $response = $this
            ->httpRequestService
            ->get($url, $headers);

        if ($response->getStatus() !== 200) {
            $this->logger->addError(
                "Fail to get Superlogica Token: {$response->getStatus()} - {$response->getContent()->msg} - URL: {$url}",
                [
                    'erp_id'        => $user->getClient()->getErpId(),
                    'domain'        => $user->getClient()->getDomain(),
                    'user_email'    => $user->getUsername()
                ]
            );

            return ErpTokenDto::error($response->getContent()->msg);
        }

        return ErpTokenDto::create($response->getContent()->token);
    }

    /**
     * @param $erpId
     * @return mixed
     */
    public function getClientErpDataById($erpId)
    {
        $headers    = [
            'Content-type'  => 'application/json',
            'access_token'  => $this->accessToken,
            'app_token'     => $this->appToken
        ];
        $url = $this::ENDPOINT_CLIENTS . "?id={$erpId}";
        $response = $this
            ->httpRequestService
            ->get($url, $headers);
        if ($response->getStatus() !== 200) {
            $this->logger->addCritical("Fail to get Superlogica Client data by ERP id: {$response->getStatus()} - {$response->getContent()->msg} - URL: {$url}");
            return false;
        }
        $response = $response->getContent();
        $response = json_encode($response[0]);
        $response = json_decode($response, true);
        return $response;
    }

    /**
     * @param $id
     * @return bool|mixed|ClientData
     */
    public function getClientById($id)
    {
        if (!$id) {
            return false;
        }

        $headers    = [
            'Content-type'  => 'application/json',
            'access_token'  => $this->accessToken,
            'app_token'     => $this->appToken
        ];

        $url = $this::ENDPOINT_CLIENTS . "?id={$id}";

        $response = $this
            ->httpRequestService
            ->get($url, $headers);

        if ($response->getStatus() !== 200) {
            $this->logger->addCritical("Fail to get Superlogica Client: {$response->getStatus()} -
           {$response->getContent()->msg} - URL: {$url}");

            return false;
        }

        if (empty($response->getContent())) {
            return false;
        }

        $data = $response->getContent()[0];

        $clientDto = new ClientData();
        $clientDto->setCompanyName($data->st_nome_sac);
        $clientDto->setDocument($data->st_cgc_sac);
        $clientDto->setAddress($data->st_endereco_sac);
        $clientDto->setDistrict($data->st_bairro_sac);
        $clientDto->setCity($data->st_cidade_sac);
        $clientDto->setState($data->st_estado_sac);
        $clientDto->setZipCode($data->st_cep_sac);

        return $clientDto;
    }

    /**
     * @param $erpId
     * @return mixed
     */
    public function unfreezeClientById($erpId)
    {
        $headers    = [
            'Content-type'  => 'application/json',
            'access_token'  => $this->accessToken,
            'app_token'     => $this->appToken
        ];
        $url = $this::ENDPOINT_CLIENTS;
        $body = [
            "ID_SACADO_SAC" => $erpId,
            "DT_CONGELAMENTO_SAC" => ""
        ];
        $response = $this
            ->httpRequestService
            ->put($url, $headers, $body);
        if ($response->getStatus() !== 200) {
            $this->logger->addCritical("Fail to unfreeze cliend by ERP ID ({$erpId}): {$response->getStatus()} - {$response->getContent()->msg} - URL: {$url}");
            return false;
        }
        return $response->getContent();
    }

    /**
     * @param $erpId
     * @return mixed
     */
    public function disableClientById($erpId)
    {
        $headers    = [
            'Content-type'  => 'application/json',
            'access_token'  => $this->accessToken,
            'app_token'     => $this->appToken
        ];
        $url = $this::ENDPOINT_CLIENTS;
        $date = new \DateTime();
        $dateFormatted = $date->format('d/m/Y');
        $body = [
            "ID_SACADO_SAC" => "{$erpId}",
            "DT_DESATIVACAO_SAC" => $dateFormatted,
            "FL_INVALIDARCOBSFUTURAS_SAC" => "1"
        ];
        $response = $this
            ->httpRequestService
            ->put($url, $headers, $body);
        return $response->getContent();
    }

    public function cancelAccount(Client $client, $author)
    {
        if (!$client->getErpId()) {
            return false;
        }

        $headers    = [
            'Content-type'  => 'application/json',
            'access_token'  => $this->accessToken,
            'app_token'     => $this->appToken
        ];

        $body       = [
            "ID_SACADO_SAC" => $client->getErpId(),
            "DT_DESATIVACAO_SAC" => date('m/d/Y'),
            "FL_INVALIDARCOBSFUTURAS_SAC" => "1"
        ];

        $response   = $this
            ->httpRequestService
            ->put($this::ENDPOINT_CLIENTS, $headers, $body);

        if ($response->getStatus() !== 200) {
            $this->logger->addCritical(
                "Fail to cancel client on Superlogica: 
                {$response->getStatus()} - {$response->getContent()[0]->msg}",
                [
                    'erp_id'        => $client->getErpId(),
                    'domain'        => $client->getDomain(),
                    'user_email'    => $author
                ]
            );
        }

        return $response->getContent()[0];
    }

    public function getClientsByClosingDate($day)
    {
        if (!$day) {
            return false;
        }

        $headers    = [
            'Content-type'  => 'application/json',
            'access_token'  => $this->accessToken,
            'app_token'     => $this->appToken
        ];

        $url = $this::ENDPOINT_CLIENTS . "?comDiaDeVencimento={$day}";

        $response = $this
            ->httpRequestService
            ->get($url, $headers);

        if ($response->getStatus() !== 200) {
            $this->logger->addCritical("Fail to get Superlogica Client: {$response->getStatus()} -
           {$response->getContent()->msg} - URL: {$url}");

            return false;
        }

        if (empty($response->getContent())) {
            return false;
        }

        $erpIds = [];

        foreach ($response->getContent() as $data) {
            array_push($erpIds, $data->id_sacado_sac);
        }

        return $erpIds;
    }

    public function getOpenedCharges()
    {
        $headers    = [
            'Content-type'  => 'application/json',
            'access_token'  => $this->accessToken,
            'app_token'     => $this->appToken
        ];

        $url = $this::ENDPOINT_CHARGES . "?&status=pendentes&exibirComposicaoDosBoletos=1&ordenacao=DT_VENCIMENTO_RECB%20ASC";

        $response = $this
            ->httpRequestService
            ->get($url, $headers);

        if ($response->getStatus() !== 200) {
            $this->logger->addCritical("Fail to get Superlogica Opened Charges: {$response->getStatus()} -
           {$response->getContent()->msg} - URL: {$url}");

            return false;
        }

        if (empty($response->getContent())) {
            return false;
        }

        return $response->getContent();
    }

    /**
     * @return mixed
     */
    public function getSmsServiceItem()
    {
        $headers    = [
            'Content-type'  => 'application/json',
            'access_token'  => $this->accessToken,
            'app_token'     => $this->appToken
        ];

        $url = $this::ENDPOINT_PRODUCTS . "?exibirSomenteServicos=1&pesquisa=sms";

        $response = $this
            ->httpRequestService
            ->get($url, $headers);

        if ($response->getStatus() !== 200) {
            $this->logger->addCritical("Fail to get Superlogica SMS Service: {$response->getStatus()} -
           {$response->getContent()->msg} - URL: {$url}");

            return false;
        }

        if (empty($response->getContent())) {
            return false;
        }

        return $response->getContent()[0];
    }

    /**
     * @param $body
     * @return mixed
     */
    public function updateChargeWithSms($body)
    {
        if (empty($body)) {
            return false;
        }

        $headers    = [
            'Content-type'  => 'application/json',
            'access_token'  => $this->accessToken,
            'app_token'     => $this->appToken
        ];

        $response   = $this
            ->httpRequestService
            ->put($this::ENDPOINT_CHARGES, $headers, $body);

        if ($response->getStatus() !== 200) {
            $this->logger->addCritical(
                "Fail to update charge with SMS on Superlogica: 
                {$response->getStatus()} - {$response->getContent()[0]->msg}",
                [
                    'body'  => $body
                ]
            );
        }

        return $response->getContent()[0];
    }
}
