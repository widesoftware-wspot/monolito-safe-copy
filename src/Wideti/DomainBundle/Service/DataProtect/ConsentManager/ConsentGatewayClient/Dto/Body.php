<?php


namespace Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentGatewayClient\Dto;


class Body implements BaseParam
{
    private $clientId;
    private $listConditionsId;

    private function __construct()
    {
    }
    public static function build(){
        return new Body();
    }

    public function addClientId($clientId){
        $this->clientId = $clientId;
        return $this;
    }

    public function setListConditionsIds(array $listConditionsId){
        foreach ($listConditionsId as $conditionId){
            $this->listConditionsId[] = ["id" => $conditionId];
        }
        return $this;
    }

    public function get(){
        if (is_null($this->clientId)){
            throw new \RuntimeException("Value clientId cannot be null");
        }
        if (is_null($this->listConditionsId)){
            throw new \RuntimeException("Value listConditionsId cannot be null");
        }
        return [
            "client_id" => $this->clientId,
            "conditions" => $this->listConditionsId
        ];
    }

}