<?php

namespace Wideti\FrontendBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\ClientSelector\ClientSelectorServiceAware;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\RouterAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RequestListener
{
    use SessionAware;
    use RouterAware;
    use ClientSelectorServiceAware;

    /**
     * @var FrontendControllerHelper
     */
    private $controllerHelper;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var CacheServiceImp
     */
    private $cacheService;

    /**
	 * @var string
	 */
	private $kernelEnv;

    /**
     * RequestListener constructor.
     * @param ConfigurationService $configurationService
     * @param FrontendControllerHelper $controllerHelper
     * @param CacheServiceImp $cacheService
     */
    public function __construct(
        ConfigurationService $configurationService,
        FrontendControllerHelper $controllerHelper,
        CacheServiceImp $cacheService,
        $kernelEnv
    ) {
        $this->configurationService = $configurationService;
        $this->controllerHelper     = $controllerHelper;
        $this->cacheService         = $cacheService;
        $this->kernelEnv = $kernelEnv;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $route  = $event->getRequest()->get('_route');

        if ($route == 'admin_oauth_redirect' && (
        ($this->kernelEnv == "prod" && $event->getRequest()->getHost() == "redirectoauthadmin.mambowifi.com") || 
        ($this->kernelEnv == "dev" && $event->getRequest()->getHost() == "redirectoauthadmin.wspot.com.br")
        )) {
            return;
        }

        if ($route == 'admin_oauth_redirect') {
            throw new NotFoundHttpException();
        }

        if (!$this->getLoggedClient() || $route == 'frontend_index' || $route == 'frontend_preview') {
            try {
                $this->session->set('wspotClient', null);
                $this->clientSelectorService->define($event->getRequest()->getHost());
            } catch (NotFoundHttpException $e) {
                throw new NotFoundHttpException();
            }
        }

        $client = $this->session->get('wspotClient');
        /**
         * @var Nas $nas
         */
        $nas    = $this->session->get(Nas::NAS_SESSION_KEY);

        if ($this->checkFrontendRoute($route)) {
            if ($route != 'frontend_pre_login' &&
                $route != 'frontend_terms_of_use' &&
                $route != 'frontend_redirection_url' &&
                $client == null && $nas == null) {
                $event->setResponse(
                    $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_pre_login'))
                );
            }
        }

        $identifier = $nas ? $nas->getAccessPointMacAddress() : null;
        $config = $this->configurationService->getByIdentifierOrDefault($identifier, $client);
        $this->controllerHelper->setTwigGlobalVariable('config', $config);
    }

    private function checkFrontendRoute($route)
    {
        if (strpos($route, 'frontend_') !== false) {
            return true;
        }
        return false;
    }
}
