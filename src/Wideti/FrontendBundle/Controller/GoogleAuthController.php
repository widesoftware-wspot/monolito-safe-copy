<?php
/**
 * Created by PhpStorm.
 * User: wideti
 * Date: 16/01/19
 * Time: 13:05
 */

namespace Wideti\FrontendBundle\Controller;


use phpDocumentor\Reflection\Types\This;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Wideti\DomainBundle\Analytics\EventLoggerManager;
use Wideti\DomainBundle\Analytics\Events\Event;
use Wideti\DomainBundle\Analytics\Events\EventIdentifier;
use Wideti\DomainBundle\Analytics\Events\EventType;
use Wideti\DomainBundle\Helpers\Controller\ControllerHelper;
use Wideti\DomainBundle\Service\Client\ClientService;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\FrontendBundle\DependencyInjection\Configuration;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Listener\NasSessionVerify\NasControllerHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wideti\DomainBundle\Document\Guest\Social;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;



class GoogleAuthController extends Controller implements NasControllerHandler
{
    use SessionAware;
    use LoggerAware;

    private $controllerHelper;
    private $googleClientId;
    private $googleClientSecret;
    private $configurationService;
    /**
     * @var ClientService
     */
    private $clientService;
    /**
     * @var EventLoggerManager
     */
    private $logManager;

    public function __construct(ControllerHelper $controllerHelper,
                                ConfigurationService $configurationService,
                                $googleClientId,
                                $googleClientSecret,
                                EventLoggerManager $logManager,
                                ClientService  $clientService
    ){
        $this->controllerHelper     = $controllerHelper;
        $this->configurationService = $configurationService;
        $this->googleClientId       = $googleClientId;
        $this->googleClientSecret   = $googleClientSecret;
        $this->logManager           = $logManager;
        $this->clientService        = $clientService;
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function googleCallback(Request $request)
    {
        $clientDomain = $request->get('state');
        if($request->get("code") && $clientDomain){
            $gclient = $this->configurationService->getGoogleClient($clientDomain);
            $gclient->authenticate($request->get("code"));
            $token = $gclient->getAccessToken();

            $oAuth = new \Google_Service_Oauth2($gclient);
            if ($oAuth) {
                $userData = $oAuth->userinfo_v2_me->get();
                $urlToRedirect = $this->controllerHelper->getRedirectUrlFromGoogleToClientDomain($clientDomain);
                return $this->redirect($urlToRedirect.http_build_query($userData), 302);
            } else {
                $this->logger->addNotice("Não foi possível iniciar o serviço de OAuth do Google");
                $urlToRedirect = $this->controllerHelper->getRedirectUrlToClientDomain($clientDomain);
                return $this->redirect($urlToRedirect, 302);
            }
        } else {
            $this->logger->addNotice("O domain ou code do cliente não está presente na requisição");
            $urlToRedirect = $this->controllerHelper->getRedirectUrlToClientDomain($clientDomain);
            return $this->redirect($urlToRedirect, 302);
        }
    }


    /**
     * @param Request $resquest
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function googleLogin(Request $resquest)
    {
        $name               = $resquest->get('name');
        $locale             = $resquest->get('locale');
        $email              = $resquest->get('email');
        $id                 = $resquest->get('id');
        $lastName           = $resquest->get('last_name');
        $givenName          = $resquest->get('given_name');
        $gender             = $resquest->get('gender');
        $age                = $resquest->get('age_range');
        $link               = $resquest->get('link');
        $picture            = $resquest->get('picture');
        $emailVerified      = $resquest->get('email_verified');

        $this->session->set(
            'guest',
            [
                'data' =>
                    [
                        'id'        => null,
                        'name'      => (isset($name))   ? $name     : null,
                        'locale'    => (isset($locale)) ? $locale   : null,
                        'email'     => (isset($email))  ? $email    : null
                    ],
                'social' =>
                    [
                        'id'   => $id,
                        'type' => Social::GOOGLE
                    ],
                'fields' =>
                    [
                        'id'         => (isset($id))                 ? $id              : null,
                        'first_name' => (isset($givenName))          ? $givenName       : null,
                        'last_name'  => (isset($lastName))           ? $lastName        : null,
                        'gender'     => (isset($gender))             ? $gender          : null,
                        'age_range'  => (isset($age))                ? $age             : null,
                        'link'       => (isset($link))               ? $link            : null,
                        'picture'    => (isset($picture))            ? $picture         : null,
                        'verified'   => (isset($emailVerified))      ? $emailVerified   : null
                    ]
            ]
        );

        $analyticEvent = new Event();
        $event = $analyticEvent->withClient($this->getLoggedClient())
            ->withEventIdentifier(EventIdentifier::GOOGLE_LOGIN)
            ->withEventType(EventType::GOOGLE_LOGIN)
            ->withNas($this->session->get(Nas::NAS_SESSION_KEY))
            ->withRequest(null)
            ->withSession($this->session)
            ->withExtraData($this->session->get('guest'))
            ->build();

        $this->logManager->sendLog($event);

        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('complete_registration'));

    }
}
