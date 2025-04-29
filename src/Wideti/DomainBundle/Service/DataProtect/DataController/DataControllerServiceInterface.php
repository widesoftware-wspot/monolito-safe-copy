<?php


namespace Wideti\DomainBundle\Service\DataProtect\DataController;


use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\DataProtect\DataController\Dto\DataControllerAgentDto;

interface DataControllerServiceInterface
{
    function save(DataControllerAgentDto $dataControllerAgentDto, Client $client);
    function getDataControllerAgent(Client $client);
    function update(DataControllerAgentDto $dataControllerAgentDto, Client $client);
}