<?php
namespace Wideti\FrontendBundle\Controller;

use GuzzleHttp\Client as GuzzleClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wideti\DomainBundle\Analytics\EventLoggerManager;
use Wideti\DomainBundle\Analytics\Events\Event;
use Wideti\DomainBundle\Analytics\Events\EventIdentifier;
use Wideti\DomainBundle\Analytics\Events\EventType;
use Wideti\DomainBundle\Document\Guest\Social;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Helpers\Controller\ControllerHelper;
use Wideti\DomainBundle\Helpers\LanguageHelper;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Client\ClientService;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\NasManager\NasServiceAware;
use Wideti\DomainBundle\Service\Template\TemplateAware;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Listener\NasSessionVerify\NasControllerHandler;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\WebFrameworkBundle\Service\Router\RouterServiceAware;

class LinkedinAuthController implements NasControllerHandler
{
    const PUBLISH_PARAMETERS_SESSION = 'PUBLISH_PARAMETERS_SESSION';

    use RouterServiceAware;
    use SessionAware;
    use LoggerAware;
    use MongoAware;
    use TwigAware;
    use TemplateAware;
    use NasServiceAware;

    /**
     * @var FrontendControllerHelper
     */
    private $controllerHelper;
    protected $linkedinClientId;
    protected $linkedinClientSecret;

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
     * LinkedinAuthController constructor.
     * @param FrontendControllerHelper $controllerHelper
     * @param ConfigurationService $configurationService
     * @param $linkedinClientId
     * @param $linkedinClientSecret
     * @param CacheServiceImp $cacheService
     */
        public function __construct(
    ControllerHelper $controllerHelper,
    ConfigurationService $configurationService,
    $linkedinClientId,
    $linkedinClientSecret,
    EventLoggerManager $logManager,
    ClientService  $clientService

    ) {
        $this->controllerHelper     = $controllerHelper;
        $this->configurationService = $configurationService;
        $this->linkedinClientId     = $linkedinClientId;
        $this->linkedinClientSecret = $linkedinClientSecret;
        $this->clientService        = $clientService;
        $this->logManager           = $logManager;
    }

    public function indexLinkedinAction(Request $request)
    {
        $state = $request->getHttpHost();
        $linkedinAuthorize = "https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id=".$this->linkedinClientId."&redirect_uri=https://linkedinauth.wspot.com.br/linkedin-callback&scope=profile%20email%20openid&state=".$state;

        return $this->controllerHelper->redirect($linkedinAuthorize);
    }

    public function linkedinCallbackAction(Request $request){
        $domain = $request->get("state");
        $code = $request->get("code");

        $guzzleClient = new GuzzleClient([
            'defaults' => [
                'exceptions' => false,
            ],
            'headers' => ['Content-Type' => 'application/json']
        ]);
        $response = $guzzleClient->post("https://www.linkedin.com/oauth/v2/accessToken", [
            "form_params" => [
                'client_id' => $this->linkedinClientId,
                'client_secret' => $this->linkedinClientSecret,
                'grant_type' => 'authorization_code',
                'redirect_uri' => 'https://linkedinauth.wspot.com.br/linkedin-callback',
                'code' => $code
            ]
        ]);

        $bodyResponse = json_decode($response->getBody()->getContents(), true);

        //DOC - https://learn.microsoft.com/en-us/linkedin/consumer/integrations/self-serve/sign-in-with-linkedin-v2
        $response2 = $guzzleClient->get("https://api.linkedin.com/v2/userinfo", [
            'headers' => [
                'Authorization' => "Bearer {$bodyResponse['access_token']}"
            ]
        ]);

        $bodyResponse2 = json_decode($response2->getBody()->getContents(), true);

        $id             = $bodyResponse2['sub'];
        $name           = $bodyResponse2['given_name'];
        $firstName      = $bodyResponse2['given_name'];
        $lastName       = $bodyResponse2['family_name'];
        $email          = $bodyResponse2['email'];
        $picture        = $bodyResponse2['picture'];
        $emailVerified  = $bodyResponse2['email_verified'];

        $guest['guest'] = [
            'data' =>
                [
                    'id'        => null,
                    'name'      => (isset($name))   ? $name     : null,
                    'email'     => (isset($email))  ? $email    : null
                ],
            'social' =>
                [
                    'id'   => $id,
                    'type' => Social::LINKEDIN
                ],
            'fields' =>
                [
                    'first_name' => (isset($firstName))          ? $firstName       : null,
                    'last_name'  => (isset($lastName))           ? $lastName        : null,
                    'picture'    => (isset($picture))            ? $picture         : null,
                    'verified'   => (isset($emailVerified))      ? $emailVerified   : null,
                    'id'         => (isset($id))                 ? $id              : null,
                    'gender'     => (isset($gender))             ? $gender          : null,
                    'age_range'  => (isset($age))                ? $age             : null,
                    'link'       => (isset($link))               ? $link            : null,
                ]
        ];


        $queryParameters = http_build_query($guest);
        $urlToRedirect = $this->getLinkedinCompleteLoginUrl($domain, $queryParameters);
        return $this->controllerHelper->redirect($urlToRedirect, 302);
    }

    private function getLinkedinCompleteLoginUrl($domain, $queryParams)
    {
        $url = 'https://<domain>/linkedin-complete-login';
        return str_replace("<domain>", $domain, $url)."?".$queryParams;
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

    public function LinkedinCompleteLogin(Request $request)
    {
        $this->session->set(
            'guest', $request->get("guest"));
        $analyticEvent = new Event();
        $event = $analyticEvent->withClient($this->getLoggedClient())
            ->withEventIdentifier(EventIdentifier::LINKEDIN_LOGIN)
            ->withEventType(EventType::LINKEDIN_LOGIN)
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

}
