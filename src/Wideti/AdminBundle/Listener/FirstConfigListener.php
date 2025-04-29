<?php

namespace Wideti\AdminBundle\Listener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Twig_Environment;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Service\Client\ClientService;
use Wideti\WebFrameworkBundle\Aware\RouterAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class FirstConfigListener
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
     * @var Twig_Environment
     */
    private $twig;

    public function __construct(
        AdminControllerHelper $controllerHelper,
        ClientService $clientService,
        Twig_Environment $twig
    ) {
        $this->controllerHelper = $controllerHelper;
        $this->clientService = $clientService;
        $this->twig = $twig;
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
            'gregwar_captcha_routing',
            'first_config_save_fields',
            'frontend_pre_login',
            'fos_js_routing_js',
            'login_admin',
            'login_check',
            'logout_admin',
            'create_first_password',
            'forgot_password',
            'notification_wrong_ap_config',
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

        if ($request->isXmlHttpRequest()) {
            return;
        }

        if (in_array($route, $allowUrls)) {
            return;
        }

        if (strpos($request->getUri(), 'generate-captcha')) {
            return;
        }

        if (!$client->getInitialSetup()) {
            if ($this->isAdminUrl($uri)) {
                $event->setResponse(
                    $this->controllerHelper->redirectToRoute("first_config_index")
                );
            }

            if ($this->isFrontendUrl($uri)) {
                $event->setResponse(
                    $this->controllerHelper->redirectToRoute("frontend_first_config_block")
                );
            }
        }
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
}
