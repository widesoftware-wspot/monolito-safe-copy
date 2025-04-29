<?php

namespace Wideti\DomainBundle\Service\Client;

use Doctrine\ORM\EntityManager;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Exception\ClientNotFoundException;
use Wideti\DomainBundle\Service\Client\Dto\ClientStatusDto;
use Wideti\DomainBundle\Service\ClientLogs\ClientLogsService;
use Wideti\DomainBundle\Service\ClientLogs\Dto\ClientLogDto;

class ClientStatusServiceImp implements ClientStatusService
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var ClientLogsService
     */
    private $clientLogsService;

    public function __construct(EntityManager $em, ClientLogsService $clientLogsService)
    {
        $this->em = $em;
        $this->clientLogsService = $clientLogsService;
    }

    /**
     * @param ClientStatusDto $clientStatus
     * @throws \Exception
     */
    public function changeStatus(ClientStatusDto $clientStatus)
    {
        $client = $clientStatus->hasClientId()
            ? $this->getClientById($clientStatus)
            : $this->getClientByErpId($clientStatus);

        if (!$client) {
            throw new ClientNotFoundException(
                "Client not exists in change status service. {$clientStatus->getUrlOrigin()}"
            );
        }

        $log = $this->createLog($clientStatus, $client);

        $client->setStatus($clientStatus->getNewStatus());
        $client->setStatusReason($clientStatus->getStatusReason());

        try{
            $this->em->flush();
            $this->clientLogsService->log($log);
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * @param ClientStatusDto $clientStatus
     * @param Client $client
     * @return ClientLogDto
     */
    private function createLog(ClientStatusDto $clientStatus, Client $client)
    {
        $logDate = new \DateTime();
        $message =
            "Change client status from {$client->getStatus()} to {$clientStatus->getNewStatus()}. Reason: {$clientStatus->getStatusReason()}";

        $log = new ClientLogDto();
        $log
            ->setClientId($client->getId())
            ->setDate($logDate->format('Y-m-d H:i:s'))
            ->setAuthor($clientStatus->getAuthor())
            ->setUrl($clientStatus->getUrlOrigin())
            ->setMethod($clientStatus->getHttpMethod())
            ->setResponse('Success')
            ->setAction($message);

        return $log;
    }

    /**
     * @param ClientStatusDto $clientStatusDto
     * @return null| Client
     */
    private function getClientByErpId(ClientStatusDto $clientStatusDto)
    {
        $client = null;
        if ($clientStatusDto->hasErpId()) {
            $client = $this
                ->em
                ->getRepository('DomainBundle:Client')
                ->findOneBy([
                    'erpId' => $clientStatusDto->getErpId()
                ]);
        }
        return $client;
    }

    private function getClientById(ClientStatusDto $clientStatusDto)
    {
        $client = null;
        if ($clientStatusDto->hasClientId()) {
            $client = $this
                ->em
                ->getRepository('DomainBundle:Client')
                ->findOneBy([
                    'id' => $clientStatusDto->getClientId()
                ]);
        }
        return $client;
    }
}
