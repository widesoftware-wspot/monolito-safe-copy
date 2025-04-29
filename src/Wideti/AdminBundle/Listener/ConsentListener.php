<?php

namespace Wideti\AdminBundle\Listener;

use Symfony\Bundle\TwigBundle\Debug\TimedTwigEngine;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Twig_Environment;
use Wideti\DomainBundle\Entity\ClientsLegalBase;
use Wideti\DomainBundle\Entity\LegalKinds;
use Wideti\DomainBundle\Gateways\Consents\GetConsentGateway;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Service\Client\ClientService;
use Wideti\DomainBundle\Service\Jaeger\TracerHeaders;
use Wideti\DomainBundle\Service\LegalBaseManager\LegalBaseManagerService;
use Wideti\WebFrameworkBundle\Aware\RouterAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class ConsentListener
{
    use SessionAware;

    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;
    /**
     * @var ClientService
     */
    private $clientService;
    /**
     * @var TwigEngine
     */
    private $twig;
    /**
     * @var GetConsentGateway
     */
    private $getConsentGateway;

    /**
     * @var LegalBaseManagerService
     */
    private $legalBaseManager;

    public function __construct(
        AdminControllerHelper $controllerHelper,
        ClientService $clientService,
        TwigEngine $twig,
        GetConsentGateway $getConsentGateway,
        LegalBaseManagerService $legalBaseManagerService
    ) {
        $this->controllerHelper = $controllerHelper;
        $this->clientService = $clientService;
        $this->twig = $twig;
        $this->getConsentGateway    = $getConsentGateway;
        $this->legalBaseManager = $legalBaseManagerService;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $uri = $request->getPathInfo();
        $allowUrls = [
            'first_config_index',
            'token_authenticate',
            'frontend_first_config_block',
            'first_config_load_template_field',
            'first_config_save_fields',
            'frontend_pre_login',
            'fos_js_routing_js',
            'login_admin',
            'login_check',
            'logout_admin',
            'create_first_password',
            'forgot_password',
            'notification_wrong_ap_config',
            'consent_manager',
            'legal_base_manager'
        ];

        $route = $this->controllerHelper->getRouter()->match($uri)['_route'];

        $clientSession = $this->getLoggedClient();

        if (!$clientSession) {
            return;
        }

        $client = $this->clientService->getClientById($clientSession->getId());

        if (!$client) {
            $this->session->set('wspotClient', null);
            return;
        }
        if ($route == 'legal_base_manager'){
            return;
        }
        $legalBaseActive = $this->legalBaseManager->getActiveLegalBase($client);
        if (is_null($legalBaseActive)){
            $response = $this->twig->renderResponse('AdminBundle:LegalBaseManager:error.html.twig');
            $event->setResponse($response);
            return;
        }
        $hasConsent = $this->hasConsent();

        if ($this->isConsentTerm($legalBaseActive) && !$hasConsent) {
        	$traceHeaders = TracerHeaders::from($event->getRequest());
            $consent = $this->getConsentGateway->get($client, 'pt_BR', $traceHeaders);
            $validatedConsent = $this->validateConsent($consent);
            $this->session->set('hasConsent', $validatedConsent);
            $hasConsent = $validatedConsent;
        }

        if ($request->isXmlHttpRequest()) {
            return;
        }

        if (in_array($route, $allowUrls)) {
            return;
        }
        if ($client->getInitialSetup() && $this->isLegitimoInteresse($legalBaseActive)){
            return;
        }

        if ($client->getInitialSetup() && !$hasConsent) {
            if ($this->isAdminUrl($uri)) {
                $event->setResponse(
                    $this->controllerHelper->redirectToRoute("consent_manager", ['hasConsent'=>$consent], 302)
                );
            }

            if ($this->isFrontendUrl($uri)) {
                $event->setResponse(
                    $this->controllerHelper->redirectToRoute("frontend_first_config_block")
                );
            }
        }

    }

    private function hasConsent()
    {
        if (is_null($this->session->get('hasConsent'))) return false;
        return $this->session->get('hasConsent');
    }

    private function isConsentTerm(ClientsLegalBase $legalBaseActive)
    {
        return !is_null($legalBaseActive) && $legalBaseActive->getLegalKind()->getKey() == LegalKinds::TERMO_CONSENTIMENTO;
    }

    private function isLegitimoInteresse(ClientsLegalBase $legalBaseActive)
    {
        return !is_null($legalBaseActive) && $legalBaseActive->getLegalKind()->getKey() == LegalKinds::LEGITIMO_INTERESSE;
    }

    /**
     * @param $uri
     * @return bool
     */
    public function isAdminUrl($uri)
    {
        $exploded = explode("/", $uri);
        return in_array("admin", $exploded);
    }

    /**
     * @param $uri
     * @return bool
     */
    public function isApiUrl($uri)
    {
        $exploded = explode("/", $uri);
        return in_array("api", $exploded);
    }

    private function isFrontendUrl($uri)
    {
        return (!$this->isAdminUrl($uri) && !$this->isApiUrl($uri));
    }

    private function validateConsent($consent) {
        if ($consent->getHasError() && $consent->getError()->getCode() == 404) {
            return false;
        }
        return true;
    }
}
