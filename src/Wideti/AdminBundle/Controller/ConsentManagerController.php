<?php


namespace Wideti\AdminBundle\Controller;


use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentGatewayClient\Exception\ClientException;
use Wideti\DomainBundle\Service\DataProtect\ConsentManager\ConsentManagerServiceInterface;
use Wideti\DomainBundle\Service\Jaeger\TracerHeaders;
use Wideti\DomainBundle\Service\AuditLogInternal\AuditLogService;
use Wideti\DomainBundle\Service\LegalBaseManager\LegalBaseManagerService;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;

class ConsentManagerController
{
    use TwigAware;
    use SessionAware;

    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;

    /**
     * @var ConsentManagerServiceInterface
     */
    private $consentManagerService;

    /**
     * @var LegalBaseManagerService
     */
    private $legalBaseManagerService;

    /**
     * @var auditLogService
     */
    private $auditLogService;


    public function __construct(
        ConsentManagerServiceInterface $consentManagerService,
        AdminControllerHelper  $adminControllerHelper,
        LegalBaseManagerService $legalBaseManagerService,
        AuditLogService $auditLogService
    ){
        $this->consentManagerService = $consentManagerService;
        $this->controllerHelper = $adminControllerHelper;
        $this->legalBaseManagerService = $legalBaseManagerService;
        $this->auditLogService = $auditLogService;
    }

    public function indexAction(Request $request){
        $hasConsent = $this->session->get('hasConsent');
        $client     = $this->getLoggedClient();
        $activeClientLegalBase = $this->legalBaseManagerService->getActiveLegalBase($client);
        return $this->render(
            'AdminBundle:ConsentManager:index.html.twig',
            [
                'hasConsent' => $hasConsent,
                'activeLegalBase' => $activeClientLegalBase
            ]
        );
    }

    public function getLastConsent(Request $request){
        try {
        	$tracerHeaders = TracerHeaders::from($request);
            $client     = $this->getLoggedClient();
            $user = $this->controllerHelper->getUser();
            $consent = $this->consentManagerService->getLastVersionConsentClient($client, $user, $tracerHeaders);
            return JsonResponse::create(
                $consent,
                200
            );
        }catch (ClientException $ex){
            return $this->handleError($ex);
        }
    }

    public function getConditions(Request $request){
        try {
            $user = $this->controllerHelper->getUser();
            $traceHeaders = TracerHeaders::from($request);
            $listConditions = $this->consentManagerService->getConditions($user, $traceHeaders);
            return JsonResponse::create(
                $listConditions,
                200
            );
        }catch (ClientException $ex){
            return $this->handleError($ex);
        }
    }

    public function createConsent(Request $request){
        try {
            /**
             * @var $client Client
             */
            $client     = $this->getLoggedClient();
            $user = $this->controllerHelper->getUser();
            $traceHeaders = TracerHeaders::from($request);
            $consentCreated = $this->consentManagerService
                ->createNewVersionConsentClient($client, $user, $request->get("conditions"), $traceHeaders);
            $this->session->set('hasConsent', true);

            $auditChanges = [
                "new" => $consentCreated['conditions']
            ];
            $this->auditLogService->createAuditLog(
                'consent', 
                Events::update()->getValue(), 
                $auditChanges, 
                true
            );

            return JsonResponse::create(
                $consentCreated,
                200
            );
        }catch (ClientException $ex){
            return $this->handleError($ex);
        }
    }

    private function handleError(ClientException $ex){
        return JsonResponse::create(
            $ex->getResponse(),
            $ex->getStatusCode()
        );
    }
}
