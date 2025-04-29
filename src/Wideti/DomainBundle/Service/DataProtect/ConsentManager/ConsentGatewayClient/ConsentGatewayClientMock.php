<?php


namespace Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentGatewayClient;


use Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentGatewayClient\Dto\ConsentParams;

class ConsentGatewayClientMock implements ConsentGatewayInterface
{

    function getConditions(ConsentParams $params)
    {
        return [
            ['id' => "45dsa", "description" => '{"pt_BR":"Envio de mensagens e notificações (promoções, notícias e demais comunicados pertinentes)"}'],
            ['id' => "78sda", "description" => '{"pt_BR":"Análise do perfil e comportamento de usuários"}'],
            ['id' => "d9sa9", "description" => '{"pt_BR":"Defesa da empresa frente a processos administrativos e judiciais, como o cumprimento da Lei do Marco Civil da Internet"}'],
            ['id' => "3sd5a", "description" => '{"pt_BR":"Enriquecimento de dados"}']
        ];
    }

    function getConsentClient(ConsentParams $params)
    {
        return [
            "id" => "4das7f5asd6sa",
            "client_id" => $params->getClientID(),
            "consent_version" => 1616970427,
            "status" => "ACTIVE",
            "conditions" => [
                ['id' => "45dsa", "description" => "Envio de mensagens e notificações (promoções, notícias e demais comunicados pertinentes)"],
                ['id' => "78sda", "description" => "Análise do perfil e comportamento de usuários"],
                ['id' => "d9sa9", "description" => "Defesa da empresa frente a processos administrativos e judiciais, como o cumprimento da Lei do Marco Civil da Internet"],
                ['id' => "3sd5a", "description" => "Enriquecimento de dados"]
            ]
        ];
    }

    function postConsentClient(ConsentParams $params)
    {
        $date = new \DateTime();
        return [
            "id" => "4das7f5asd6sa",
            "client_id" => $params->getBody()["client_id"],
            "consent_version" => $date->getTimestamp(),
            "status" => "ACTIVE",
            "conditions" => [
                ['id' => "45dsa", "description" => '{"pt_BR":"Envio de mensagens e notificações (promoções, notícias e demais comunicados pertinentes)"}'],
//                ['id' => "78sda", "description" => "Análise do perfil e comportamento de usuários"],
//                ['id' => "d9sa9", "description" => "Defesa da empresa frente a processos administrativos e judiciais, como o cumprimento da Lei do Marco Civil da Internet"],
                ['id' => "3sd5a", "description" => '{"pt_BR":"Enriquecimento de dados"}']
            ]
        ];
    }

    public function deleteConsentClient(ConsentParams $params)
    {
        return [];
    }
}