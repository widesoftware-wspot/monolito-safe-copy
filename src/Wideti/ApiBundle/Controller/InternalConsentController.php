<?php

namespace Wideti\ApiBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\LegalKinds;
use Wideti\DomainBundle\Gateways\Consents\GetConsentGateway;
use Wideti\DomainBundle\Gateways\Consents\RevokeSignedConsentGateway;
use Wideti\DomainBundle\Service\Jaeger\TracerHeaders;
use Wideti\DomainBundle\Service\LegalBaseManager\LegalBaseManagerService;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;

class InternalConsentController implements ApiResource
{
    use EntityManagerAware;

    /**
     * @var DocumentManager
     */
    private $mongo;

    /**
     * @var GetConsentGateway
     */
    private $getConsentGateway;

    /**
     * @var RevokeSignedConsentGateway
     */
    private $revokeSignedConsentGateway;

    /**
     * @var LegalBaseManagerService
     */
    private $legalBaseManagerService;

    public function __construct(
        DocumentManager $mongo,
        GetConsentGateway $getConsentGateway,
        RevokeSignedConsentGateway $revokeSignedConsentGateway,
        LegalBaseManagerService $legalBaseManagerService)
    {
        $this->mongo                      = $mongo;
        $this->getConsentGateway          = $getConsentGateway;
        $this->revokeSignedConsentGateway = $revokeSignedConsentGateway;
        $this->legalBaseManagerService    = $legalBaseManagerService;
    }

    const RESOURCE_NAME = 'internal_consent';

    public function revokeSignedConsent(Request $request)
    {
        $clientId = $request->get("client_id");
        if (is_null($clientId) || $clientId == ""){
            return JsonResponse::create(['error' => "É necessário passar a query string 'client_id'."], 400);
        }
        $guestId = $request->get("guest_id");
        if (is_null($guestId) || $guestId == ""){
            return JsonResponse::create(['error' => "É necessário passar a query string 'guest_id'."], 400);
        }

        $client = $this->getClientRepository()->findOneBy(['id' => (int)$clientId]);
        if (is_null($client)){
            return JsonResponse::create(['error' => "Cliente não encontrado"], 404);
        }
        $legalBaseActive = $this->legalBaseManagerService->getActiveLegalBase($client);
        if ($legalBaseActive->getLegalKind()->getKey() == LegalKinds::TERMO_CONSENTIMENTO){
            $guestRepository = $this->getGuestRepository($client);
            $guest = $guestRepository->findOneBy(["mysql" => (int)$guestId]);
            if (is_null($guest)){
                return JsonResponse::create(['error' => "Visitante não encontrado"], 404);
            }

            $headers = TracerHeaders::from($request);
            $consent = $this->getConsentGateway->get($client, "pt_BR", $headers);
            if ($consent->getHasError()){
                return JsonResponse::create(['error' => "Erro ao buscar o consentimento do cliente"], 500);
            }
            $signedConsentRevoked = $this->revokeSignedConsentGateway->revoke($guest, $consent, $headers);
            if ($signedConsentRevoked->hasError()){
                if ($signedConsentRevoked->getError()->getCode() == 404){
                    return JsonResponse::create(
                        [
                            'error' => "Assinatura do visitante não encontrada",
                        ], $signedConsentRevoked->getError()->getCode());
                }
                return JsonResponse::create(
                    [
                        'error' => "Erro ao revogar o consentimento do visitante",
                        "extra" => $signedConsentRevoked->getError()->getMessage()
                    ], 500);
            }
            $guest->setHasConsentRevoke(true);
            $guestRepository->getDocumentManager()->persist($guest);
            $guestRepository->getDocumentManager()->flush();

            return JsonResponse::create(["message" => $signedConsentRevoked->getMessage() ], 200);
        }
        return JsonResponse::create(
            [
                "error" => "Não foi possível revogar o consentimento do visitante. O cliente não possui o 'termo de consentimento' como base legal."
            ], 422);
    }


    /**
     * @return \Wideti\DomainBundle\Repository\ClientRepository
     */
    private function getClientRepository()
    {
        return $this->em->getRepository(Client::class);
    }


    /**
     * @param Client $client
     * @return \Wideti\DomainBundle\Document\Repository\GuestRepository
     */
    private function getGuestRepository(Client $client)
    {

        $this->mongo
            ->getConfiguration()
            ->setDefaultDB($client->getMongoDatabaseName());

        $newMongo = $this->mongo->create(
            $this->mongo->getConnection(),
            $this->mongo->getConfiguration(),
            $this->mongo->getEventManager()
        );

        return $newMongo->getRepository(Guest::class);
    }


    public function getResourceName()
    {
        return self::RESOURCE_NAME;
    }
}
