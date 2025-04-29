<?php

namespace Wideti\DomainBundle\Service\NasManager;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\ApiEgoi as ApiEgoi;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\ApiEgoi\ApiEgoiServiceAware;
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

class NasEgoiIntegrationStep implements NasStepInterface
{
    use EntityManagerAware;
    use RouterAware;
    use SessionAware;
    use ModuleAware;
    use CustomFieldsAware;
    use ApiEgoiServiceAware;

    /**
     * @var Auditor
     */
    private $auditor;

    /**
     * NasEgoiIntegrationStep constructor.
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
     * @return bool
     * @throws AuditException
     */
    public function process(Guest $guest, Nas $nas = null, Client $client)
    {
        $hasEmailField = $this->customFieldsService->getFieldByNameType('email');

        if ($this->moduleService->modulePermission('egoi') && $hasEmailField) {
	        /**
	         * @var ApiEgoi $integration
	         */
            $integration = $this->getIntegration($nas);

            if (!$integration) {
                return false;
            }

            $autoIntegration = $integration->isEnableAutoIntegration();
            if (!$autoIntegration) {
                return false;
            }

            if (!$guest->getReturning()) {
                $this->apiEgoiService->subscribe($integration, $guest);
                // Audit
                $event = $this->auditor
                    ->newEvent()
                    ->withClient($client->getId())
                    ->withSource(Kinds::egoi(), $integration->getId())
                    ->onTarget(Kinds::guest(), $guest->getMysql())
                    ->withType(Events::send())
                    ->addDescription(AuditEvent::PT_BR, 'Visitante enviado para integração do E-GOI')
                    ->addDescription(AuditEvent::EN_US, 'Visitor sent for E-GOI integration')
                    ->addDescription(AuditEvent::ES_ES, 'Visitante enviado para la integración de E-GOI');
                $this->auditor->push($event);
            }
        }
    }

    /**
     * @param Nas $nas
     * @return ApiEgoi|null
     */
    private function getIntegration(Nas $nas)
    {
	    $client = $this->getLoggedClient();

        /**
         * Primeiro verifica se existe integração criada para TODOS pontos de acesso
         * @var $allAps ApiEgoi
         */
        $allAps = $this->em
            ->getRepository('DomainBundle:ApiEgoi')
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
         * @var $byAp ApiEgoi
         */
        $byAp = $this->em
            ->getRepository('DomainBundle:ApiEgoi')
            ->getByAccessPoint($client, $accessPoint);

        if ($byAp) {
            return $byAp;
        }

        return null;
    }
}
