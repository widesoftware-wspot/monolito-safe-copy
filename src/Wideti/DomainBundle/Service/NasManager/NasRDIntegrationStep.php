<?php

namespace Wideti\DomainBundle\Service\NasManager;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\ApiRDStation;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\ApiRDStation\ApiRDStationService;
use Wideti\DomainBundle\Service\ApiRDStation\ApiRDStationServiceAware;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\AuditException;
use Wideti\DomainBundle\Service\AuditLogs\Auditor;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\RouterAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class NasRDIntegrationStep implements NasStepInterface
{
    use EntityManagerAware;
    use RouterAware;
    use SessionAware;
    use ModuleAware;
    use CustomFieldsAware;
    use ApiRDStationServiceAware;

    /**
     * @var Auditor
     */
    private $auditor;

    /**
     * NasRDIntegrationStep constructor.
     * @param Auditor $auditor
     */
    public function __construct(Auditor $auditor)
    {
        $this->auditor = $auditor;
    }

    /**
     * @param Guest $guest
     * @param Nas|null $nas
     * @param Client $client
     * @return false
     * @throws AuditException
     */
    public function process(Guest $guest, Nas $nas = null, Client $client)
    {
        $hasEmailField = $this->customFieldsService->getFieldByNameType('email');

        if ($this->moduleService->modulePermission('rd_station') && $hasEmailField) {
            $integration = $this->getIntegration($nas);

            if (!$integration) {
                return false;
            }

            $token = $integration->getToken();
            if (!$token) {
                return false;
            }

            $autoIntegration = $integration->isEnableAutoIntegration();
            if (!$autoIntegration) {
                return false;
            }

            if (!$guest->getReturning()) {
                if ($client->isWhiteLabel()) {
                    $this->apiRDStationService->conversion($token, $nas, $guest, ApiRDStationService::ID_ON_CREATE_WL."_de_".$client->getDomain());
                } else {
                    $this->apiRDStationService->conversion($token, $nas, $guest, ApiRDStationService::ID_ON_CREATE);
                }
                $this->audit($integration->getId(), $client, $guest);

            } else {
                $lastAccess = $this->session->get('lastAccess')
                    ?: new \DateTime(date('Y-m-d', $guest->getLastAccess()->sec));
                $today      = new \DateTime('NOW');
                $diff = $lastAccess->diff($today);
                if ($diff->days > 0) {
                    if ($client->isWhiteLabel()) {
                        $this->apiRDStationService->conversion($token, $nas, $guest, ApiRDStationService::ID_FIRST_ACCESS_WL."_de_".$client->getDomain());
                    } else {
                        $this->apiRDStationService->conversion($token, $nas, $guest, ApiRDStationService::ID_FIRST_ACCESS);
                    }
                    $this->audit($integration->getId(), $client, $guest);
                }
            }
        }
    }

    /**
     * @param $tokenId
     * @param Client $client
     * @param Guest $guest
     * @throws AuditException
     */
    private function audit($tokenId, Client $client, Guest $guest) {
        $event = $this->auditor
            ->newEvent()
            ->withClient($client->getId())
            ->withSource(Kinds::rdStation(), $tokenId)
            ->onTarget(Kinds::guest(), $guest->getMysql())
            ->withType(Events::send())
            ->addDescription(AuditEvent::PT_BR, 'Visitante enviado para integração do RD Station')
            ->addDescription(AuditEvent::EN_US, 'Visitor sent for RD Station integration')
            ->addDescription(AuditEvent::ES_ES, 'Visitante enviado para la integración de RD Station');
        $this->auditor->push($event);
    }

    private function getIntegration(Nas $nas)
    {
	    $client = $this->getLoggedClient();

        /**
         * Primeiro verifica se existe integração criada para TODOS pontos de acesso
         * @var $allAps ApiRDStation
         */
        $allAps = $this->em
            ->getRepository('DomainBundle:ApiRDStation')
            ->getByAllAccessPoints($client);

        if ($allAps) {
            return $allAps;
        }

        /**
         * @var $accessPoint AccessPoints
         */
        $accessPoint = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->findOneBy([
	            'client'     => $client,
                'identifier' => $nas->getAccessPointMacAddress()
            ]);

        if (!$accessPoint) {
            return null;
        }

        /**
         * Se não achar, busca pelo ponto de acesso em que o visitante está acessando
         * @var $byAp ApiRDStation
         */
        $byAp = $this->em
            ->getRepository('DomainBundle:ApiRDStation')
            ->getByAccessPoint($client, $accessPoint);

        if ($byAp) {
            return $byAp;
        }

        return null;
    }
}
