<?php


namespace Wideti\DomainBundle\Service\IntegrationValidator;


use Wideti\DomainBundle\Service\IntegrationValidator\Dto\IntegrationValidate;
use Wideti\DomainBundle\Service\HttpRequest\Dto\HttpResponse;
use Wideti\DomainBundle\Service\HttpRequest\HttpRequestService;

class RdIntegrationValidator implements IntegrationValidatorInterface
{

    /**
     * @var HttpRequestService
     */
    private $httpRequestService;
    private $conversionEndpoint = 'https://www.rdstation.com.br/api/1.3/conversions';

    public function __construct(HttpRequestService $httpRequestService, $conversionEndpoint)
    {
        $this->httpRequestService = $httpRequestService;
        $this->conversionEndpoint = $conversionEndpoint;
    }

    /**
     * @param string $token
     * @return void|IntegrationValidate
     */
    public function validate($token)
    {
        $lead = $this->createLead($token);
        $header = ['Content-Type'=>'application/json'];
        /**
         * @var HttpResponse $result
         */
        $result = $this->httpRequestService->post($this->conversionEndpoint, $header, $lead);
        if ($result->getStatus() == 200){
            return IntegrationValidate::valid();
        }
        if ($result->getStatus() == 401){
            return IntegrationValidate::fail(
                "[401 Unauthorized] Houve um erro na integração. Verifique se o token público está correto ou veja se há alguma pendência em sua conta do RD.");
        }
        return IntegrationValidate::fail(
            "Ocorreu o seguinte erro na integração: [" . $result->getStatus() . "| ". $result->getContent() . "]");
    }

    private function createLead($token)
    {
        return [
            'token_rdstation' => $token,
            'identificador' => 'teste_integracao',
            'autoriza_receber_email' => 'N/I',
            'email' => 'no-reply@mambofi.com',
            'horario' => date('d/m/Y H:i:s'),
            'mac_ponto_de_acesso' => '11-11-11-11-11-11',
            'nome_ponto_de_acesso' => '11-11-11-11-11-11',
            'dominio' => 'teste.integracao.mambowifi.com',
            'traffic_source' => '(none)',
            'birthday' => '16/04',
            'phone' => '1999999999',
            'dialCodePhone' => '55',
            'name' => 'Teste Integração'
        ];
    }
}