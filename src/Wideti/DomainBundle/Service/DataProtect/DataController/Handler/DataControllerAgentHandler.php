<?php


namespace Wideti\DomainBundle\Service\DataProtect\DataController\Handler;


use Wideti\DomainBundle\Entity\DataControllerAgent;
use Wideti\DomainBundle\Service\DataProtect\DataController\Dto\DataControllerAgentDto;

class DataControllerAgentHandler
{

    public static function create(DataControllerAgentDto $dataControllerAgentDto)
    {
        return self::handle($dataControllerAgentDto);
    }

    public static function change(DataControllerAgent $dataControllerAgent, DataControllerAgentDto $dataControllerAgentDto)
    {
        return self::handle($dataControllerAgentDto, $dataControllerAgent);
    }

    private static function handle(DataControllerAgentDto $dataControllerAgentDto, $dataControllerAgent = null)
    {
        if (is_null($dataControllerAgent)){
            $dataControllerAgent = new DataControllerAgent();
        }
        $dataControllerAgent->setBirthday($dataControllerAgentDto->getBirthday());
        $dataControllerAgent->setCpf($dataControllerAgentDto->getCpf());
        $dataControllerAgent->setEmail($dataControllerAgentDto->getEmail());
        $dataControllerAgent->setFullName($dataControllerAgentDto->getFullName());
        $dataControllerAgent->setJobOccupation($dataControllerAgentDto->getJobOccupation());
        $dataControllerAgent->setPhoneNumber($dataControllerAgentDto->getPhoneNumber());
        return $dataControllerAgent;
    }
}