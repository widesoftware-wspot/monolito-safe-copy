<?php
namespace Wideti\FrontendBundle\Controller;

use Abraham\TwitterOAuth\TwitterOAuth;
use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Analytics\EventLoggerManager;
use Wideti\DomainBundle\Analytics\Events\Event;
use Wideti\DomainBundle\Analytics\Events\EventIdentifier;
use Wideti\DomainBundle\Analytics\Events\EventType;
use Wideti\DomainBundle\Document\Guest\Social;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\FrontendBundle\Listener\NasSessionVerify\NasControllerHandler;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Service\Router\RouterServiceAware;

class TwitterAuthController implements NasControllerHandler
{
    use RouterServiceAware;
    use SessionAware;
    use LoggerAware;

    /**
     * @var FrontendControllerHelper
     */
    private $controllerHelper;
    protected $consumerKey;
    protected $consumerSecret;
    /**
     * @var EventLoggerManager
     */
    private $logManager;

    public function __construct(FrontendControllerHelper $controllerHelper,
                                $consumerKey,
                                $consumerSecret,
                                EventLoggerManager $logManager
    ){
        $this->controllerHelper = $controllerHelper;
        $this->consumerKey      = $consumerKey;
        $this->consumerSecret   = $consumerSecret;
        $this->logManager       = $logManager;
    }

    public function redirectAction(Request $request)
    {
        $domain = $request->get('domain');
        $queryParams = "oauth_token={$request->get('oauth_token')}&oauth_verifier={$request->get('oauth_verifier')}";
        $callbackUrl = $domain . "{$this->getParameter('twitter_callback_oauth_url')}?" . $queryParams;

        return $this->controllerHelper->redirect($callbackUrl, 302);
    }

    public function indexAction(Request $request)
    {
        $twitter = new TwitterOAuth(
            $this->consumerKey,
            $this->consumerSecret
        );

        $domain = 'https://' . $request->getHttpHost();

        $requestToken = $twitter->oauth(
            'oauth/request_token',
            [
                'oauth_callback' => "{$this->getParameter('twitter_redirect_oauth_url')}?domain={$domain}"
            ]
        );

        $authUrl = $twitter->url(
            'oauth/authorize',
            [
                'oauth_token' => $requestToken['oauth_token']
            ]
        );

        return $this->controllerHelper->redirect($authUrl);
    }

    public function callbackAction(Request $request)
    {
        try {
            $data = $request->query->all();

            if (array_key_exists("denied", $data)) {
                return $this->controllerHelper->redirect(
                    $this->controllerHelper->generateUrl('frontend_index').'?socialError=87'
                );
            }

            $twitter = new TwitterOAuth(
                $this->consumerKey,
                $this->consumerSecret
            );

            $token = $twitter->oauth(
                'oauth/access_token',
                [
                    'oauth_token' => $data['oauth_token'],
                    'oauth_verifier' => $data['oauth_verifier']
                ]
            );

            $twitter = new TwitterOAuth(
                $this->consumerKey,
                $this->consumerSecret,
                $token['oauth_token'],
                $token['oauth_token_secret']
            );

            $me = $twitter->get(
                'account/verify_credentials',
                [
                    'oauth_token' => $token['oauth_token'],
                    'oauth_token_secret' => $token['oauth_token_secret']
                ]
            );

            $twitterObj = get_object_vars($me);

            if (!$twitterObj['id']) {
                return $this->controllerHelper->redirect(
                    $this->controllerHelper->generateUrl('frontend_index').'?socialError=3'
                );
            }

        } catch (\Exception $e) {
            $this->logger->addCritical($e->getMessage());
            $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
        }

        $this->session->set(
            'guest',
            [
                'data' =>
                [
                    'id'     => null,
                    'name'   => $twitterObj['name'],
                    'locale' => 'pt_br',
                    'gender' => null,
                    'email'  => null
                ],
                'social' =>
                [
                    'id'   => $twitterObj['id'],
                    'type' => Social::TWITTER
                ],
                'fields' => []
            ]
        );

        $analyticEvent = new Event();
        $event = $analyticEvent->withClient($this->getLoggedClient())
            ->withEventIdentifier(EventIdentifier::TWITTER_LOGIN)
            ->withEventType(EventType::TWITTER_LOGIN)
            ->withNas($this->session->get(Nas::NAS_SESSION_KEY))
            ->withRequest(null)
            ->withSession($this->session)
            ->withExtraData($this->session->get('guest'))
            ->build();

        $this->logManager->sendLog($event);

        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('complete_registration'));
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
}
