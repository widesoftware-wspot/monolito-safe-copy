<?php


namespace Wideti\DomainBundle\Service\LegalBaseManager;


use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Session\Session;
use Wideti\DomainBundle\Entity\ApiWSpot;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\ClientsLegalBase;
use Wideti\DomainBundle\Entity\LegalKinds;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\Auditor;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;
use Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentGatewayClient\Exception\ClientException;
use Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentManagerServiceInterface;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;

class LegalBaseManagerService
{
    use SecurityAware;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Auditor
     */
    private $auditor;

    /**
     * @var ConsentManagerServiceInterface
     */
    private $consentManagerService;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        EntityManager $em,
        Auditor $auditor,
        ConsentManagerServiceInterface $consentManagerService,
        Session $session,
        Logger $logger
    ) {
        $this->em = $em;
        $this->auditor = $auditor;
        $this->consentManagerService = $consentManagerService;
        $this->session = $session;
        $this->logger = $logger;
    }

    public function defineLegalBase(Client $client, $legalKindKey, $traceHeaders = [])
    {
        $user = $this->getUser();
        // Audit
        $event = $this->auditor->newEvent();
        if ($user instanceof Users){
            $event
                ->withSource(Kinds::userAdmin(), $user->getId());
        }elseif ($user instanceof ApiWSpot){
            $event
                ->withSource(Kinds::apiToken(), $user->getId());
        }
        $event
            ->withClient($client->getId())
            ->onTarget(Kinds::client(), $client->getId());

        $activeClientLegalBase = $this->getActiveLegalBase($client);
        if (!is_null($activeClientLegalBase)){
            if ($activeClientLegalBase->getLegalKind()->getKey() == $legalKindKey) return;
            $this->disable($activeClientLegalBase, $user, $traceHeaders);

            $event
                ->withType(Events::update())
                ->addDescription(AuditEvent::PT_BR, "Usuário alterou a base legal de {$activeClientLegalBase->getLegalKind()->getKey()} para {$legalKindKey}")
                ->addDescription(AuditEvent::EN_US, "User changed legal basis from {$activeClientLegalBase->getLegalKind()->getKey()} to {$legalKindKey}")
                ->addDescription(AuditEvent::ES_ES, "El usuario cambió la base legal de {$activeClientLegalBase->getLegalKind()->getKey()} a {$legalKindKey}");
            $this->auditor->push($event);
        }else{
            $event
                ->withType(Events::create())
                ->addDescription(AuditEvent::PT_BR, "Usuário criou a base legal {$legalKindKey}")
                ->addDescription(AuditEvent::EN_US, "User created legal base {$legalKindKey}")
                ->addDescription(AuditEvent::ES_ES, "El usuario creó la base legal {$legalKindKey}");
        }
        $this->auditor->push($event);

        $legalKind = $this->getLegalKind($legalKindKey);
        $clientLegalBase = ClientsLegalBase::createActive($client, $legalKind);

        $this->em->getRepository(ClientsLegalBase::class)
            ->save($clientLegalBase);
    }

    public function forceDisableConsentTerm(Client $client, $traceHeaders = []){

        try {
            $consent = $this->consentManagerService
                ->getLastVersionConsentClient($client, $this->getUser(), $traceHeaders);
            $this->consentManagerService
                ->deleteConsentClient($client, $this->getUser(), $consent["id"], $traceHeaders);
            $this->session->set('hasConsent', false);
        }catch (ClientException $ex){
            if ($ex->getStatusCode() != 404){
                $this->logger->addCritical("Falha ao desabilitar o termo de consentimento. ClientException Erro ". $ex->getMessage(), [
                    'status_code' => $ex->getStatusCode(),
                    'response' => $ex->getResponse()
                ]);
                throw $ex;
            }
        }
    }

    public function hasConsentTerm(Client $client, $traceHeaders = []){
        try {
            $consent = $this->consentManagerService
                ->getLastVersionConsentClient($client, $this->getUser(), $traceHeaders);
            return true;
        }catch (ClientException $ex){
            if ($ex->getStatusCode() == 404){
                return false;
            }
            $this->logger->addCritical("Falha ao obter o termo de consentimento (hasConsentTerm). ClientException Erro ". $ex->getMessage(), [
                'status_code' => $ex->getStatusCode(),
                'response' => $ex->getResponse()
            ]);
            throw $ex;
        }
    }

    private function disable(ClientsLegalBase $clientsLegalBase, Users $user, $traceHeaders = [])
    {
        if ($clientsLegalBase->getLegalKind()->getKey() == LegalKinds::TERMO_CONSENTIMENTO){
            try {
                $consent = $this->consentManagerService
                    ->getLastVersionConsentClient($clientsLegalBase->getClient(), $user, $traceHeaders);
                $this->consentManagerService
                    ->deleteConsentClient($clientsLegalBase->getClient(), $user, $consent["id"], $traceHeaders);
                $this->session->set('hasConsent', false);
            }catch (ClientException $ex){
                if ($ex->getStatusCode() != 404){
                    throw $ex;
                }
            }
        }
        $clientsLegalBase->setActive(false);
        $this->em->getRepository(ClientsLegalBase::class)
            ->save($clientsLegalBase);
    }

    /**
     * @param Client $client
     * @return ClientsLegalBase
     */
    public function getActiveLegalBase(Client $client)
    {
        return $this->em->getRepository(ClientsLegalBase::class)
            ->findOneBy(['client' => $client, 'active' => true]);
    }

    /**
     * @param $legalBase
     * @return LegalKinds
     */
    public function getLegalKind($legalBase)
    {
        return $this->em->getRepository(LegalKinds::class)
            ->findOneBy(['key' => $legalBase]);
    }

    public function getAllLegalKinds()
    {
        return $this->em->getRepository(LegalKinds::class)->findAll();
    }
}