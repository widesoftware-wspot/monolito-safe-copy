<?php

namespace Wideti\DomainBundle\Service\NasManager;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Repository\AccessPointsRepository;
use Wideti\DomainBundle\Service\NasManager\Dto\AuthReport\AccessPointEvent;
use Wideti\DomainBundle\Service\Queue\QueueService;
use Wideti\FrontendBundle\Factory\Nas;

// TODO
// Verificar se a função pública "getAccessPointEvent" é usada para algo, em
// caso negativo remover completamente esse "step"
//
// O "process" dessa classe foi desativada em Maio de 2019 no commit
// 6ef3f04830c7759219979bbe39a2a646659e6fa0
// Aqui foi removido apenas a construção do client SQS (queueService)
// porque em sua construção é feita uma requisição para a AWS, essas
// requisições causaram alguns incidentes no dia 29/02/2024:
// - https://zabbix.widesoftware.com.br/tr_events.php?triggerid=309727&eventid=10452539
// - https://zabbix.widesoftware.com.br/tr_events.php?triggerid=309727&eventid=10452535
//
class NasAuthEventStep implements NasStepInterface
{

    /**
     * @var QueueService
     */
    private $queueService;
    private $accessPointRepository;

    public function __construct($sqsKey, $sqsSecret, $queueRegion, $queueName, AccessPointsRepository $accessPointRepository)
    {
        $this->accessPointRepository = $accessPointRepository;
    }

    public function process(Guest $guest, Nas $nas = null, Client $client)
    {
    	//TODO já enviamos um lote considerável para validação e análise, por este motivo estamos 'desativando' essa funcionalidade por enquanto.
	    return NasStepInterface::NO_RETURN;
    }

    /**
     * @param Nas $nas
     * @return AccessPointEvent
     */
    public function getAccessPointEvent(Nas $nas)
    {
        /**
         * @var AccessPoints $accessPoint
         */
        $accessPoint = $this->accessPointRepository->findOneBy([
            'identifier' => $nas->getAccessPointMacAddress()
        ]);

        return $accessPoint
            ? AccessPointEvent::createFrom($accessPoint)
            : AccessPointEvent::createEmpty();
    }
}