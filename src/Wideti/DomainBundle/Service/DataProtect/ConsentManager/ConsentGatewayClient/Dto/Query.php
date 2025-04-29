<?php


namespace Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentGatewayClient\Dto;


class Query implements BaseParam
{
    private $conditionType;

    private function __construct()
    {
    }

    public static function build(){
        return new Query();
    }

    public function addConditionType($conditionType){
        $this->conditionType = $conditionType;
        return $this;
    }

    public function get(){
        if (is_null($this->conditionType)){
            throw new \RuntimeException("Value conditionType cannot be null");
        }
        return [
            "condition_type" => $this->conditionType
        ];
    }
}