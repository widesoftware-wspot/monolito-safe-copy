<?php


namespace Wideti\DomainBundle\Service\DataProtect\DataController;


use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Repository\DataControllerAgentRepository;
use Wideti\DomainBundle\Service\DataProtect\DataController\Dto\DataControllerAgentDto;
use Wideti\DomainBundle\Service\DataProtect\DataController\Exceptions\DataControllerNotFoundRuntimeException;
use Wideti\DomainBundle\Service\DataProtect\DataController\Handler\DataControllerAgentHandler;

class DataControllerService implements DataControllerServiceInterface
{
    /**
     * @var DataControllerAgentRepository
     */
    private $dataControllerAgentRepository;

    /**
     * DataControllerService constructor.
     * @param DataControllerAgentRepository $dataControllerAgentRepository
     */
    public function __construct(DataControllerAgentRepository $dataControllerAgentRepository)
    {
        $this->dataControllerAgentRepository = $dataControllerAgentRepository;
    }

    function save(DataControllerAgentDto $dataControllerAgentDto, Client $client)
    {
        $dataControllerAgent = DataControllerAgentHandler::create($dataControllerAgentDto);
        $dataControllerAgent->setClient($client);
        $this->dataControllerAgentRepository->save($dataControllerAgent);
    }

    /**
     * @param Client $client
     * @return DataControllerAgentDto
     */
    function getDataControllerAgent(Client $client)
    {
        $dataControllerAgent = $this->dataControllerAgentRepository->getDataControllerAgentByClient($client);
        if (is_null($dataControllerAgent)) throw new DataControllerNotFoundRuntimeException("Data Controller not found!");
        return DataControllerAgentDto::createByDataControllerAgent($dataControllerAgent);
    }

    function update(DataControllerAgentDto $dataControllerAgentDto, Client $client)
    {
        $dataControllerAgent = $this->dataControllerAgentRepository->getDataControllerAgentByClient($client);
        $dataControllerAgent = DataControllerAgentHandler::change($dataControllerAgent, $dataControllerAgentDto);
        $this->dataControllerAgentRepository->save($dataControllerAgent);
    }
}