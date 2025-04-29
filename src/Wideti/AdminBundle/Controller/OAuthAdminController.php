<?php
namespace Wideti\AdminBundle\Controller;

use Exception;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use League\OAuth2\Client\Provider\GenericProvider;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wideti\DomainBundle\Document\Guest\Social;
use Wideti\DomainBundle\Entity\AdminOAuthLogin;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Exception\OAuthException;
use Wideti\DomainBundle\Service\User\UserServiceAware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wideti\DomainBundle\Exception\ClientWasDisabledException;
use Wideti\DomainBundle\Entity\Client as WspotClient;


class OAuthAdminController extends Controller
{
    use SessionAware;
    use LoggerAware;
    use SecurityAware;
    use EntityManagerAware;
    use UserServiceAware;
     /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;

    private $fieldLogin;
    /**
     * @var GenericProvider
     */
    private $provider;
    /**
     * @var AdminOAuthLogin
     */
    private $oauthParams;

    /**
	 * @var string
	 */
	private $environmentsAvailableSubdomains;

    /**
	 * @var string
	 */
    private $redirectUri;

    /**
	 * @var string
	 */
	private $kernelEnv;

    /**
     * OAuthAdminController constructor.
     * @param AdminControllerHelper $controllerHelper
	 * @param $kernelEnv
	 * @param $environmentsAvailableSubdomains
     */
    public function __construct(
        AdminControllerHelper $controllerHelper,
        $kernelEnv,
        $environmentsAvailableSubdomains
    ) {
        $this->controllerHelper                = $controllerHelper;
        $this->kernelEnv                       = $kernelEnv;
		$this->environmentsAvailableSubdomains = $environmentsAvailableSubdomains;
    }


    private function getClientAndConnection($clientErpId)
    {
        $oauthParams = $this->em->getRepository("DomainBundle:AdminOAuthLogin")->findOneBy(['erpId' => $clientErpId]);

        if (!$oauthParams) {
            throw new Exception("Client OAuth params not found on database.");
        }

        $oauthClientIdParameter = [];
        if (strpos($oauthParams->getUrl(), "idp.mambowifi.com/realms") !== false) {
            $oauthClientIdParameter = ['oauth_client_id' => $oauthParams->getClientId()];
        }
        $this->oauthParams = $oauthParams;
        $adfsHost = $oauthParams->getUrl();
        $authorizeUrl = $oauthParams->getAuthorizeUrl();
        $tokenUrl = $oauthParams->getTokenUrl();
        $clientId = $oauthParams->getClientId();
        $this->resource = $oauthParams->getResource();
        $clientSecret = $oauthParams->getClientSecret();
        $this->fieldLogin = $oauthParams->getFieldLogin();
        $fullUrl = $this->controllerHelper->generateUrl('admin_oauth_callback', $oauthClientIdParameter, UrlGeneratorInterface::ABSOLUTE_URL);
        $redirectUri = rtrim(preg_replace('/https|http/', 'https', $fullUrl, 1), '/');
        $globalRedirectUri = $this->kernelEnv == "prod"
        ? "https://redirectoauthadmin.mambowifi.com/oauth-admin/redirect"
        : "https://redirectoauthadmin.wspot.com.br/app_dev.php/oauth-admin/redirect";

        $prov = [
            'clientId' => $clientId,
            'redirectUri' => $globalRedirectUri,
            'urlAuthorize' => $authorizeUrl,
            'urlAccessToken' => $tokenUrl,
            'urlResourceOwnerDetails' => "{$adfsHost}/?",
            'clientSecret' => $clientSecret
        ];
        $this->redirectUri = base64_encode($redirectUri);
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
        $clientErpId = $this->getLoggedClient()->getErpId();
        try {
            $this->getClientAndConnection($clientErpId);
            $authUrl = $this->buildAuthUrl($this->oauthParams->getScope());
            return $this->controllerHelper->redirect($authUrl);
        } catch (Exception $e) {
            $this->logger->addCritical($e->getMessage());
            return $this->controllerHelper->redirectToRoute('login_admin', ['oAuthError' => "Erro ao tentar buscar informações do provedor de identidade, tente novamente mais tarde ou contate o suporte"]);
        }
    }

    private function replaceFullDomainToGetDomain($fullDomain)
	{

	    if (strpos($fullDomain, 'mambowifi')) {
            $fullSubDomain = explode(".mambowifi.com", $fullDomain);
        } else {
            $fullSubDomain = explode(".wspot.com.br", $fullDomain);
        }

	    if (strpos($fullSubDomain[0], '.') !== false) {
	    	$subDomain = explode(".", $fullSubDomain[0]);

	    	if (!in_array($subDomain[1], $this->environmentsAvailableSubdomains)) {
			    throw new NotFoundHttpException('Domain not found: Invalid redirect uri');
		    }

	    	return $subDomain[0];
	    }

		return $fullSubDomain[0];
	}

    public function redirectAction(Request $request)
    {
        try {
            $path = '/app_dev.php/oauth-admin/callback';
            $url = base64_decode($request->get("state"));
            if ($this->kernelEnv == "prod") {
                $path = '/oauth-admin/callback';
            }
            $parsedUrl = parse_url($url);
            if (
                !(isset($parsedUrl['scheme']) && $parsedUrl['scheme'] === 'https') ||
                !(isset($parsedUrl['host']) && preg_match('/^[a-zA-Z0-9.-]+$/', $parsedUrl['host'])) ||
                !(isset($parsedUrl['path']) && $parsedUrl['path'] === $path)
            ) {
                $this->logger->addCritical('Redirect oauth admin - uri recebida fora do padrão: ' . $url);
                throw new NotFoundHttpException('Invalid redirect uri');
            }
            $domain = $parsedUrl['host'];
            if(strpos($domain, "wspot.com.br") || strpos($domain, "mambowifi") ) {
                $domain = $this->replaceFullDomainToGetDomain($domain);
            }
            $client = $this->em
                ->getRepository('DomainBundle:Client')
                ->findOneByDomain($domain)
            ;

            if ($client == null) {
                throw new NotFoundHttpException('Domain not found: Invalid redirect uri');
            }

            if ($client->getStatus() == WspotClient::STATUS_INACTIVE) {
                throw new ClientWasDisabledException('This client was disabled! Invalid redirect uri');
            }

            $params = $request->query->all();
            $urlComParametros = $url . '?' . http_build_query($params);
            return $this->controllerHelper->redirect($urlComParametros);
        } catch (Exception $e) {
            $this->logger->addCritical($e->getMessage());
            throw new NotFoundHttpException('Failed to redirect');
        }
    }

    public function callbackAction(Request $request)
    {
        $clientErpId = $this->getLoggedClient()->getErpId();
        try {
            $this->getClientAndConnection($clientErpId);
        } catch (Exception $ex) {
            $this->logger->addCritical('Client OAuth params not found!!!');
            return $this->controllerHelper->redirectToRoute('login_admin', ['oAuthError' => "Erro ao tentar buscar informações do provedor de identidade, tente novamente mais tarde ou contate o suporte"]);
        }

        try {
            $data = $request->query->all();
            $accessToken = $this->provider->getAccessToken('authorization_code', [
                'code' => $data['code'],
            ]);
            $oauthClientId = $this->oauthParams->getClientId();
            $tokenType = $this->oauthParams->getTokenType();
            $payload = [];

            if ($tokenType == "access_token") {
                $payload = $this->getPayload($accessToken->getToken());
            } elseif ($tokenType == "id_token") {
                $payload = $this->getPayload($accessToken->getValues()[$tokenType]);
            }

            $user = $this->getUserInfo($payload);
            $loginField = $this->oauthParams->getFieldLogin();
            $username = $user[$loginField];
            $userRegistered = $this->em
			->getRepository('DomainBundle:Users')
			->findOneBy(['username' => $username, 'client' => null, 'createdAtOauth' => 1, 'erpId' => $clientErpId ]);
            ##-## busca por erp id nulo e então seta o erp id
            if (!$userRegistered) {
                $userRegistered = $this->em
			    ->getRepository('DomainBundle:Users')
			    ->findOneBy(['username' => $username, 'client' => null, 'createdAtOauth' => 1, 'erpId' => null ]);
            }
            $rolesIdentifers = $this->oauthParams->getRolesIdentifiers();
            if (!array_key_exists("groups", $user)) {
                throw new OAuthException("Usuário encontrado não pertence a nenhum grupo de permissão para acesso, contate o administrador");
            }
            $groups = $user["groups"];
            $role = null;
            foreach ($groups as $group) {
                if (array_key_exists($group, $rolesIdentifers)) {
                    $role = $rolesIdentifers[$group];
                    break;
                }
            }
            $roleFinded = null;
            if ($role) {
                $roleFinded = $this->em
                ->getRepository('DomainBundle:Roles')
                ->findOneBy(['role' => $role]);
            } else {
                throw new OAuthException("Usuário encontrado não pertence a nenhum grupo de permissão para acesso, contate o administrador");
            }
            if (!$roleFinded) {
                throw new OAuthException("Usuário encontrado não pertence a nenhum grupo de permissão para acesso, contate o administrador");
            }
            $userToAuthenticate = null;
            if ($userRegistered) {
                $userRegistered->setRole($roleFinded);
                $userRegistered->setStatus(Users::ACTIVE);
                $userRegistered->setErpId($clientErpId);
            } else {
                $newUser = new Users();

                $newUser->setUsername($username);
                $fieldName = $this->oauthParams->getFieldName();
                $newUser->setNome($user[$fieldName]);
                $newUser->setStatus(Users::ACTIVE);
                $newUser->setReceiveReportMail(0);
                $newUser->setReportMailLanguage(0);
                $newUser->setFinancialManager(0);
                $newUser->setResetedToStrongPassword(1);
                $newUser->setRole($roleFinded);
                $newUser->setErpId($clientErpId);
                $userRegistered = $this->userService->registerByOauth($newUser, true);                
            }
            $passwordToken = new UsernamePasswordToken($userRegistered, null, "admin_secured_area", $userRegistered->getRoles());
            $this->controllerHelper->getContainer()->get('security.token_storage')->setToken($passwordToken);
            $event = new InteractiveLoginEvent($request, $passwordToken);
            $this->controllerHelper->getContainer()->get('event_dispatcher')->dispatch('security.interactive_login', $event);
            
            return $this->controllerHelper->redirectToRoute('admin_dashboard');
        } catch (OAuthException $ex) {
            $this->logger->addCritical($ex->getMessage());
            return $this->controllerHelper->redirectToRoute('login_admin', ['oAuthError' => $ex->getMessage()]);
        } catch (Exception $e) {
            if ($e->getMessage() != 'Required parameter not passed: "code"') {
                $this->logger->addCritical($e->getMessage());
            }
            return $this->controllerHelper->redirectToRoute('login_admin', ['oAuthError' => "Erro ao tentar buscar informações do provedor de identidade, tente novamente mais tarde ou contate o suporte"]);
        }
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
            'scope' => [$scope],
            'state' => $this->redirectUri
        ];
        $authorizationUrl = $this->provider->getAuthorizationUrl($options);
        return "{$authorizationUrl}&resource={$this->oauthParams->getResource()}";
    }

}
