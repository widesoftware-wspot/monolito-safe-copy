<?php
namespace Wideti\FrontendBundle\Controller;

use Wideti\DomainBundle\Entity\Campaign;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\RedirectUrl\RedirectUrlService;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Listener\NasSessionVerify\NasControllerHandler;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\RouterAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class RedirectController implements NasControllerHandler
{
    use SessionAware;
    use EntityManagerAware;
    use RouterAware;

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
     * @var RedirectUrlService
     */
    private $redirectUrl;

    /**
     * RedirectController constructor.
     * @param ConfigurationService $configurationService
     * @param FrontendControllerHelper $controllerHelper
     * @param CacheServiceImp $cacheService
     * @param RedirectUrlService $redirectUrl
     */
    public function __construct(
        ConfigurationService $configurationService,
        FrontendControllerHelper $controllerHelper,
        CacheServiceImp $cacheService,
        RedirectUrlService $redirectUrl
    ) {
        $this->configurationService = $configurationService;
        $this->controllerHelper     = $controllerHelper;
        $this->cacheService         = $cacheService;
        $this->redirectUrl          = $redirectUrl;
    }

    public function redirectAction()
    {
        $redirectUrl = $this->session->get('redirectUrl');
        $nas         = $this->session->get(Nas::NAS_SESSION_KEY);

        if (!$redirectUrl) {            
            $client      = $this->getLoggedClient();
            $redirectUrl = $this->redirectUrl->getRedirectUrl($nas, $client);
        }

        $redirectCTAtUrl = null;

        if ($this->session->get("callToActionUrlIsSet")) {
            $redirectCTAtUrl = $this->session->get("redirectUrl");
        }

        if ($nas->getVendorName() == "unifi" || $nas->getVendorName() == "unifinew") {
            $redirectUrl     = str_replace("https://", "http://", $redirectUrl);
            $redirectCTAtUrl = str_replace("https://", "http://", $redirectCTAtUrl);
        }        

        if ($redirectCTAtUrl) {
            return $this->controllerHelper->redirect($redirectCTAtUrl);
        }        

        return $this->controllerHelper->redirect($redirectUrl);
    }
}
