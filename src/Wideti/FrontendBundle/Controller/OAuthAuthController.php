<?php
namespace Wideti\FrontendBundle\Controller;

use Exception;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use League\OAuth2\Client\Provider\GenericProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wideti\DomainBundle\Document\Guest\Social;
use Wideti\DomainBundle\Entity\OAuthLogin;
use Wideti\DomainBundle\Helpers\Controller\FrontendControllerHelper;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsService;
use Wideti\FrontendBundle\Listener\NasSessionVerify\NasControllerHandler;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Service\Router\RouterServiceAware;

class OAuthAuthController implements NasControllerHandler
{
    use RouterServiceAware;
    use SessionAware;
    use LoggerAware;
    use EntityManagerAware;

    /**
     * @var FrontendControllerHelper
     */
    private $controllerHelper;
    private $fieldLogin;
    /**
     * @var GenericProvider
     */
    private $provider;
    /**
     * @var OAuthLogin
     */
    private $oauthParams;

    /**
     * @var CustomFieldsService
     */
    private $customFieldsService;

    public function __construct(FrontendControllerHelper $controllerHelper, CustomFieldsService $customFieldsService)
    {
        $this->controllerHelper = $controllerHelper;
        $this->customFieldsService = $customFieldsService;
    }

    private function getClientAndConnection($oauthClientId = null)
    {
        $clientDomain = $this->getLoggedClient()->getDomain();
        /**
         * @var OAuthLogin $oauthParams
         */
        if (!$oauthClientId) {
            try {
                $oauthClientId = $request->query->get('oauthClientId');
            } catch (\Exception $ex) {
                $this->logger->addCritical('Client OAuth ClientId params not found!!!');
                return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
            }
        }

        $oauthParams = $this->em->getRepository("DomainBundle:OAuthLogin")->findOneBy(['clientId' => $oauthClientId, 'domain' => $clientDomain]);

        if (!$oauthParams) {
            throw new Exception("Client OAuth params not found on database.");
        }

        $oauthClientIdParameter = [];  // used by PHPSimpleSaml to define which auth source use (multi sso)
        if (strpos($oauthParams->getUrl(), "idp.mambowifi.com/realms") !== false) {
            $oauthClientIdParameter = ['oauth_client_id' => $oauthParams->getClientId()];
        }
        $this->oauthParams = $oauthParams;
        $this->session->set('oauthClientId', $oauthParams->getClientId());
        $adfsHost = $oauthParams->getUrl();
        $authorizeUrl = $oauthParams->getAuthorizeUrl();
        $tokenUrl = $oauthParams->getTokenUrl();
        $clientId = $oauthParams->getClientId();
        $this->resource = $oauthParams->getResource();
        $clientSecret = $oauthParams->getClientSecret();
        $this->fieldLogin = $oauthParams->getFieldLogin();
        $fullUrl = $this->controllerHelper->generateUrl('frontend_oauth_callback', $oauthClientIdParameter, UrlGeneratorInterface::ABSOLUTE_URL);
        $redirectUri = rtrim(preg_replace('/https|http/', 'https', $fullUrl, 1), '/');

        $prov = [
            'clientId' => $clientId,
            'redirectUri' => $redirectUri,
            'urlAuthorize' => $authorizeUrl,
            'urlAccessToken' => $tokenUrl,
            'urlResourceOwnerDetails' => "{$adfsHost}/?",
            'clientSecret' => $clientSecret
        ];

        $this->provider = new GenericProvider($prov);

        $guzzyClient = new Client([
            'defaults' => [
                RequestOptions::CONNECT_TIMEOUT => 5,
                RequestOptions::ALLOW_REDIRECTS => true
            ],
            RequestOptions::VERIFY => false
        ]);

        $this->provider->setHttpClient($guzzyClient);
    }

    public function indexAction(Request $request)
    {
        try {
            $oauthClientId = $request->query->get('oauth_client_id');
            $this->getClientAndConnection($oauthClientId);
        } catch (\Exception $ex) {
            $this->logger->addCritical('Client OAuth params not found!!!');
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
        }

        $clientDomain = $this->getLoggedClient()->getDomain();

        $authUrl = $this->buildAuthUrl($this->oauthParams->getScope());
        return $this->controllerHelper->redirect($authUrl);
    }

    public function callbackAction(Request $request)
    {
        try {
            $this->getClientAndConnection($this->session->get('oauthClientId'));
        } catch (\Exception $ex) {
            $this->logger->addCritical('Client OAuth params not found!!!');
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
        }

        try {
            $data = $request->query->all();
            $accessToken = $this->provider->getAccessToken('authorization_code', [
                'code' => $data['code'],
            ]);

            $clientDomain = $this->getLoggedClient()->getDomain();
            $oauthClientId = $this->oauthParams->getClientId();
            $tokenType = $this->oauthParams->getTokenType();
            $payload = [];

            if ($tokenType == "access_token") {
                $payload = $this->getPayload($accessToken->getToken());
            } elseif ($tokenType == "id_token") {
                $payload = $this->getPayload($accessToken->getValues()[$tokenType]);
            }



            $this->generateLog($payload);
            $user = $this->getUserInfo($payload);
            $loginField = $this->oauthParams->getFieldLogin();
        } catch (Exception $e) {
            if ($e->getMessage() != 'Required parameter not passed: "code"') {
                $this->logger->addCritical($e->getMessage());
            }

            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('frontend_index'));
        }

        $locale = $this->session->get('locale') ? $this->session->get('locale') : 'pt_br';


        if ($this->hasCPFFormat($user[$loginField]) && ($locale == "pt_br")) {
            $isValidDocument = $this->validateCPF($user[$loginField]);
            if (!$isValidDocument) {
                $this->logger->addCritical('invalid CPF on custom field in SSO');
                throw new Exception("invalid CPF on custom field in SSO");
            }
            $user[$loginField] = $this->cleanDocument($user[$loginField]);
        }

        $this->session->set(
            'guest',
            [
                'data' =>
                [
                    'id'     => null,
                    'locale' => $locale,
                    $loginField => $user[$loginField],
                    'field_login' =>  $loginField
                ],
                'oauth_data' => $user,
                'social' => [
                    'type' => Social::OAUTH
                ]
            ]
        );


        return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('complete_registration_oauth', ['oauth_client_id' => $oauthClientId]));
    }

    /**
     * @param $accessToken
     * @return object|null
     */
    private function getPayload($accessToken)
    {
        $tks = explode('.', $accessToken);
        list($headb64, $bodyb64, $cryptob64) = $tks;
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($bodyb64));

        return json_decode(json_encode($payload), true);
    }

    private function getUserInfo($payload)
    {
        $userInfo = [
            $this->fieldLogin => $payload[$this->fieldLogin],
        ];

        foreach ($payload as $field => $value) {
            $userInfo[$field] = $value;
        }
        return $userInfo;
    }

    /**
     * @param $scope
     * @return string
     */
    private function buildAuthUrl($scope)
    {
        $options = [
            'scope' => [$scope]
        ];
        $authorizationUrl = $this->provider->getAuthorizationUrl($options);
        return "{$authorizationUrl}&resource={$this->oauthParams->getResource()}";
    }

    private function generateLog($payload) {
        $logMessage = '{"OAUTH2-LOG": {';
        $endLogMessage = "}}";
        $domain = $this->getLoggedClient()->getDomain();
        $loginField = $this->customFieldsService->getLoginFieldIdentifier();
        $logMessage.="\"client_domain\": \"$domain\",";
        $logMessage.="\"login_field\": \"$loginField\",";
        $logMessage.="\"payload\": ". json_encode($payload) .""; 
        $logMessage.=$endLogMessage;
    }

    /**
     * @param $cpf
     * @return array|string|string[]|null
     *
     */
    function cleanDocument($cpf) {
       return preg_replace('/[^0-9]/', '', $cpf);
    }

    function hasCPFFormat($texto) {
        return preg_match('/^\d{3}\.\d{3}\.\d{3}-\d{2}$/', $texto) === 1;
    }

    function validateCPF($cpf) {

        // Extrai somente os números
        $cpf = preg_replace( '/[^0-9]/is', '', $cpf );

        // Verifica se foi informado todos os digitos corretamente
        if (strlen($cpf) != 11) {
            return false;
        }

        // Verifica se foi informada uma sequência de digitos repetidos. Ex: 111.111.111-11
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Faz o calculo para validar o CPF
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        return true;

    }
}
