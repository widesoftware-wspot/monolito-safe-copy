<?php

namespace Wideti\DomainBundle\Service\RDStation;

use GuzzleHttp\Client as GuzzleClient;

class RDStationService
{
    protected $rdAuthToken;
    private $guzzleClient;

    const RD_BASE_URL                           = "https://www.rdstation.com.br";
    const TAG_WSPOT_PROBLEMA_CRIACAO_AUTOMATICA = 'wspot_problema_criacao_automatica';
    const TAG_ACESSOU_PAINEL_WSPOT              = 'acessou_painel_mambo_wifi';

    public function __construct($rdAuthToken)
    {
        $this->rdAuthToken = $rdAuthToken;

        $this->guzzleClient =  new GuzzleClient([
            'base_uri' => self::RD_BASE_URL,
            'defaults' => [
                'exceptions' => false,
            ],
            'headers' => ['Content-Type' => 'application/json']
        ]);
    }

    /**
     * Insere uma ou mais tags em um lead.
     * http://ajuda.rdstation.com.br/hc/pt-br/articles/203282269-Como-inserir-Tags-via-API
     * @param
     * @param array $arrayTags
     * @return bool
     * @internal param array $tags
     * @throws \Exception
     */
    public function insertTagLead($email, array $arrayTags)
    {
        if (!$email) {
            throw new \Exception('Email is required to insert tag on RDStation');
        }

        $tags       = implode(', ', $arrayTags);

        $parameters = [
            'auth_token' => $this->getRdAuthToken(),
            'tags'       => $tags
        ];

        $response = $this->guzzleClient->post("/api/1.2/leads/$email/tags", ['body' => json_encode($parameters)]);
        $response = json_decode($response->getBody()->getContents(), true);

        return true;
    }

    /**
     * @return mixed
     */
    public function getRdEndPoint()
    {
        return $this->rdEndPoint;
    }

    /**
     * @return mixed
     */
    public function getRdAuthToken()
    {
        return $this->rdAuthToken;
    }
}
