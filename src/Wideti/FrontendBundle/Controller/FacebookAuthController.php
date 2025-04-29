<?php
namespace Wideti\FrontendBundle\Controller;

use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wideti\DomainBundle\Analytics\EventLoggerManager;
use Wideti\DomainBundle\Analytics\Events\Event;
use Wideti\DomainBundle\Analytics\Events\EventIdentifier;
use Wideti\DomainBundle\Analytics\Events\EventType;
use Wideti\DomainBundle\Document\Guest\Social;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Helpers\FacebookRedirectLikeAndShareHelper;
use Wideti\DomainBundle\Helpers\LanguageHelper;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Client\ClientService;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\NasManager\NasServiceAware;
use Wideti\DomainBundle\Service\Social\Facebook\Dto\PublishParametersDto;
use Wideti\DomainBundle\Service\Social\Facebook\FacebookCsrfFixServiceAware;
use Wideti\DomainBundle\Service\Social\Facebook\FacebookPersistentDataHandlerAware;
use Wideti\DomainBundle\Service\Template\TemplateAware;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Listener\NasSessionVerify\NasControllerHandler;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\WebFrameworkBundle\Service\Router\RouterServiceAware;

class FacebookAuthController implements NasControllerHandler
{
    const PUBLISH_PARAMETERS_SESSION = 'PUBLISH_PARAMETERS_SESSION';
    const FACEBOOK_URL_PATTERN = '/lm.facebook.com/';

    use RouterServiceAware;
    use SessionAware;
    use LoggerAware;
    use MongoAware;
    use TwigAware;
    use TemplateAware;
    use NasServiceAware;
    use FacebookPersistentDataHandlerAware;
    use FacebookCsrfFixServiceAware;

    /**
     * @var FrontendControllerHelper
     */
    private $controllerHelper;
    protected $facebookAppId;
    protected $facebookAppSecret;
    protected $fb;

    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var CacheServiceImp
     */
    private $cacheService;

    /**
     * @var EventLoggerManager
     */
    private $logManager;

    /**
     * @var ClientService
     */
    private $clientService;

    /**
     * FacebookAuthController constructor.
     * @param ConfigurationService $configurationService
     * @param FrontendControllerHelper $controllerHelper
     * @param $facebookAppId
     * @param $facebookAppSecret
     * @param CacheServiceImp $cacheService
     * @throws FacebookSDKException
     */
    public function __construct(
        ConfigurationService $configurationService,
        FrontendControllerHelper $controllerHelper,
        $facebookAppId,
        $facebookAppSecret,
        $facebookAppGraphVersion,
        CacheServiceImp $cacheService,
        EventLoggerManager $logManager,
        ClientService $clientService

    ) {
        $this->configurationService = $configurationService;
        $this->controllerHelper     = $controllerHelper;
        $this->facebookAppId        = $facebookAppId;
        $this->facebookAppSecret    = $facebookAppSecret;
        $this->clientService        = $clientService;

        $this->fb = new Facebook([
            'persistent_data_handler'   => $this->facebookPersistentDataHandler,
            'app_id'                    => $this->facebookAppId,
            'app_secret'                => $this->facebookAppSecret,
            'default_graph_version'     => $facebookAppGraphVersion
        ]);

        $this->cacheService     = $cacheService;
        $this->logManager       = $logManager;
    }

    public function indexAction(Request $request)
    {
        $this->session->remove('fb_token');
        $permissions = ['email'];

        /**
         * @var Client $client
         */
        $client = $this->getLoggedClient();
        try {
            $redirectHelper = $this->fb->getRedirectLoginHelper();
            $redirectHelper->getPersistentDataHandler()->set("state", $request->getHttpHost());
            $fbRedirectUrl = $redirectHelper->getLoginUrl($this->getRedirectOauthUrl(), $permissions);
            $this->facebookCsrfFix->saveCSRFOnCache($fbRedirectUrl);
            return $this->controllerHelper->redirect($fbRedirectUrl);
        } catch (FacebookSDKException $e) {
            $this->facebookErrorLogHandler($e);
            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl('frontend_index').'?facebookError=1'
            );
        }
    }

    public function callbackDefaultAction(Request $request){
        $domain = $request->get("state");
        $error = $request->get("error");
        if (!is_null($error) && $error !== ""){
            return $this->controllerHelper->redirect(
                $this->getFrontendIndexUrl($domain).'?facebookError=1'
            );
        }
        try {
            $redirectHelper = $this->fb->getRedirectLoginHelper();
            $redirectHelper->getPersistentDataHandler()->set('state', $domain);
            $accessToken = $redirectHelper->getAccessToken();
            $this->fb->setDefaultAccessToken($accessToken);

            $response       = $this->fb->get(
                '/me?fields=id,email,name,first_name,last_name,age_range,link,gender,locale,picture,verified'
            );
            $data           = $response->getGraphUser();
            if (!$data['id']) {
                return $this->controllerHelper->redirect(
                    $this->getFrontendIndexUrl($domain).'?socialError=1'
                );
            }

            if ($data == null) {
                $this->logger->addCritical('User data came empty from Facebook');
                return $this->controllerHelper->redirect(
                    $this->getFrontendIndexUrl($domain).'?facebookError=1'
                );
            }

            $guest["guest"] = [
                        'data' => [
                            'id'        => null,
                            'name'      => (isset($data['name'])) ? $data['name'] : null,
                            'locale'    => (isset($data['locale'])) ? $data['locale'] : null,
                            'email'     => (isset($data['email'])) ? $data['email'] : null
                        ],
                        'social' => [
                            'id'   => $data['id'],
                            'type' => Social::FACEBOOK
                        ],
                        'fields' => [
                            'id'         => (isset($data['id'])) ? $data['id'] : null,
                            'first_name' => (isset($data['first_name'])) ? $data['first_name'] : null,
                            'last_name'  => (isset($data['last_name'])) ? $data['last_name'] : null,
                            'gender'     => (isset($data['gender'])) ? $data['gender'] : null,
                            'age_range'  => (isset($data['age_range'])) ? $data['age_range']['min'] : null,
                            'link'       => (isset($data['link'])) ? $data['link'] : null,
                            'picture'    => (isset($data['picture'])) ? $data['picture']['url'] : null,
                            'verified'   => (isset($data['verified'])) ? $data['verified'] : null
                        ]
                    ];

            $queryParams = http_build_query($guest);
            $urlToRedirect = $this->getFacebookCompleteLoginUrl($domain, $queryParams);
            return $this->controllerHelper->redirect($urlToRedirect, 302);
        } catch (FacebookSDKException $e) {
            $requestedDomain = StringHelper::getClientDomainByUrl($request->getHost());
            if ($requestedDomain != "facebookauth"){
                return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index', [
                    'socialError' => 1
                ]));
            }
            $this->facebookErrorLogHandler($e);
            $uri = $this->getFrontendIndexUrl($domain).'?facebookError=1';
            return $this->controllerHelper->redirect($uri);
        }
    }

    /**
     * @param string $parameter
     * @return string
     */
    private function getParameter($parameter)
    {
        $container = $this->controllerHelper->getContainer();
        return $container->getParameter($parameter);
    }

    private function getFrontendIndexUrl($domain)
    {
        $url = $this->getParameter("frontend_index_url");
        return str_replace("<domain>", $domain, $url);
    }

    private function getFacebookCompleteLoginUrl($domain, $queryParams)
    {
        $url = $this->getParameter("facebook_complete_login_url");
        return str_replace("<domain>", $domain, $url)."?".$queryParams;
    }

    private function getRedirectOauthUrl()
    {
        return $this->getParameter("facebook_redirect_oauth_url");
    }

    public function facebookCompleteLogin(Request $request)
    {
        $this->session->set(
            'guest', $request->get("guest"));
        $analyticEvent = new Event();
        $event = $analyticEvent->withClient($this->getLoggedClient())
            ->withEventIdentifier(EventIdentifier::FACEBOOK_LOGIN)
            ->withEventType(EventType::FACEBOOK_LOGIN)
            ->withNas($this->session->get(Nas::NAS_SESSION_KEY))
            ->withRequest(null)
            ->withSession($this->session)
            ->withExtraData($this->session->get('guest'))
            ->build();

        $this->logManager->sendLog($event);

        return $this->controllerHelper->redirect(
            $this->controllerHelper->generateUrl(
                'complete_registration',
                []
            )
        );
    }

    public function publishActions(Request $request)
    {
        $nas            = $this->session->get(Nas::NAS_SESSION_KEY);
        $client         = $this->getLoggedClient();
        $facebookHelper = new FacebookRedirectLikeAndShareHelper($this->session);

        $publishParams = $this->getPublishParameters($request);

	    if (!isset($publishParams) || (!$publishParams->getGuestId() && !$this->session->get('guest'))) {
		    return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
	    }

        if ($this->isFacebookHttpReferer($request)) {
            return $this->controllerHelper->redirect(
                $this->controllerHelper->generateUrl('complete_registration_confirmation', [
                    'guest' => $publishParams->getGuestId(),
                    'socialType' => $publishParams->getSocialType()
                ])
            );
        }

        if ($publishParams->getGuestId()) {
            $guest = $this->mongo->getRepository('DomainBundle:Guest\Guest')->findOneBy([
                'id' => $publishParams->getGuestId()
            ]);
        } else {
            $guestData  = $this->session->get('guest');
            $guest      = $this->mongo
                ->getRepository('DomainBundle:Guest\Guest')
                ->findOneBy([
                    'email' => $guestData['data']['email']
                ]);
        }

        if (!$guest) {
            $this->logger->addWarning('Guest null on publishActions() - ' . $publishParams->getGuestId());
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index', [
                'socialError' => 1
            ]));
        }

        /**
         * TODO -- (WSPOTNEW-3603)
         * TODO -- Aqui verificamos se existe o identificador do visitante na sessão, é o cenário onde ocorre o erro
         * TODO -- no iOS, que redireciona para a tela de Curtir/Compartilhar Novamente. Então, se cair aqui, vamos
         * TODO -- redirecionar o visitante direto para o nasService->process();
         */
        $hasFacebookShareOnSession = $facebookHelper->getFromSession($guest->getMysql());
        if ($hasFacebookShareOnSession) {
            return $this->nasService->process($guest, $nas);
        }

        $facebookConfig = $this->configurationService->getFacebookConfiguration($nas, $client);

        if ($publishParams->getAction() == 'loginOnly' || (!$facebookConfig->isShare() && !$facebookConfig->isLike())) {
            return $this->nasService->process($guest, $nas);
        }

        $response = new Response();
        $response->headers->add([
            'Access-Control-Allow-Origin' => "*"
        ]);

        /**
         * TODO -- (WSPOTNEW-3603)
         * TODO -- Aqui é redirecionado para tela do facebook para Curtir/Compartilhar
         * TODO -- A ideia é aqui guardar um valor na sessão para identificarmos que o usuário X foi redirecionado para
         * TODO -- Curtir/Compartilhar
         */
        $facebookHelper->putOnSession($guest->getMysql());

        $analyticEvent = new Event();
        $event = $analyticEvent->withClient($this->getLoggedClient())
            ->withEventIdentifier(EventIdentifier::FACEBOOK_PUBLISH_ACTION)
            ->withEventType(EventType::FACEBOOK_PUBLISH_ACTION)
            ->withNas($nas)
            ->withRequest($request)
            ->withSession($this->session)
            ->withExtraData($this->session->get('guest'))
            ->build();

        $this->logManager->sendLog($event);

        return $this->render(
            'FrontendBundle:Social:facebookPublish.html.twig',
            [
                'facebookConfig'    => $facebookConfig,
                'guestId'           => $publishParams->getGuestId(),
                'socialType'        => $publishParams->getSocialType(),
                'template'          => $this->templateService->templateSettings($this->session->get('campaignId')),
                'language'          => LanguageHelper::convertLocaleToLanguage($request->get('_locale'))
            ],
            $response
        );
    }

    /**
     * @param Request $request
     * @return PublishParametersDto
     */
    private function getPublishParameters(Request $request)
    {
        $parameters = new PublishParametersDto();
        $isFacebookHttpReferer = $this->isFacebookHttpReferer($request);

        if ($isFacebookHttpReferer) {
            $parameters = $this->session->get(self::PUBLISH_PARAMETERS_SESSION);
        } else {
            $parameters
                ->setAction($request->query->get('action'))
                ->setGuestId($request->get('guest'))
                ->setSocialType($request->get('socialType'));
            $this->session->set(self::PUBLISH_PARAMETERS_SESSION, $parameters);
        }

        return $parameters;
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function isFacebookHttpReferer(Request $request)
    {
        return (boolean)preg_match(self::FACEBOOK_URL_PATTERN, $request->headers->get('referer'));
    }

    private function checkRequestURL(Request $request)
    {
        $regex = '/wspot\.com\.br$/m';
        $host = $request->getHost();

        return (bool)preg_match_all($regex, $host);
    }

    protected function facebookHelper()
    {
        return $this->fb->getRedirectLoginHelper();
    }

    /**
     * @param FacebookSDKException $e
     *
     * Errors documentation
     * https://developers.facebook.com/docs/php/FacebookResponseException/5.0.0
     * https://developers.facebook.com/docs/graph-api/using-graph-api/#errors
     * https://developers.facebook.com/docs/php/FacebookSDKException/5.0.0
     */
    private function facebookErrorLogHandler(FacebookSDKException $e)
    {
        $criticalCodes = [1, 17, 191,1609005];

        if (in_array($e->getCode(), $criticalCodes)) {
            $this->logger->addCritical("Facebook critical code[{$e->getCode()}]: {$e->getMessage()}");
        } else {
            $this->logger->addWarning("Facebook warning code[{$e->getCode()}]: {$e->getMessage()}");
        }
    }
}
