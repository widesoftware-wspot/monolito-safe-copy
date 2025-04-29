<?php
namespace Wideti\AdminBundle\Controller;

use Documents\User;
use Firebase\JWT\JWT;
use GuzzleHttp\Client as GuzzleClient;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Wideti\AdminBundle\Form\LoginType;
use Wideti\DomainBundle\Dto\PurchaseLoginDto;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\Cookie\CookieHelper;
use Wideti\DomainBundle\Security\IDP\Authentication;
use Wideti\DomainBundle\Service\Auth\AuthorizationToken\AuthorizationToken;

class LoginController extends Controller
{
    private $maxAttempts = 5;
    private $blockDuration = 300; // 5 minutos

    public function loginAction(Request $request)
    {
        $session = $request->getSession();
        $session->set('wspotClient', null);
        $client = $this->get('core.service.client_selector')->define($request->getHost());

        $clientModules = $this->loadClientModules($client);
        $customFields = $this->loadClientCustomFields($client);

        $modulesShortCodes = [];
        $customFieldsIdentifiers = [];

        foreach ($clientModules as $module) {
            $modulesShortCodes[] = $module->getShortCode();
        }

        foreach ($customFields as $field ) {
            $customFieldsIdentifiers[] = $field->getIdentifier();
        }
        $session->set("fields", $customFieldsIdentifiers);

        $tokenLoginStatus = $this->processTokenPurchaseLogin($client, $request);
        if ($tokenLoginStatus->isLoginSuccess()) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $purchaseLoginStatus = $this->processPurchaseLogin($client, $request);
        if ($purchaseLoginStatus->isLoginSuccess()) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $form = $this->createForm(LoginType::class);

        $host = $request->getHost();
        $isRegularDomain = (strpos($host, 'wspot.com.br') || strpos($host, 'mambowifi'));
        $session->set('isRegularDomain', $isRegularDomain);

        $session->set('modulesActive', $modulesShortCodes);

        $error = $session->get(Security::AUTHENTICATION_ERROR);
        $session->remove(Security::AUTHENTICATION_ERROR);
        $session->set("clientHost", $request->getHost());
		$session->set("panel_access", "client_admin");

        $username = $session->get(Security::LAST_USERNAME);
        $redisBlockKey = 'login_block_' . $username;

        if ($error) {
            $this->incrementLoginAttempts($username);
        }

        return $this->render('AdminBundle:Login:login.html.twig', array(
            'form'          => $form->createView(),
            'last_username' => $username,
            'error'         => $error,
            'blockedTime'   => $this->get('core.service.cache')->getTTL($redisBlockKey),
            'isWhiteLabel'  => $client->isWhiteLabel(),
            'oauth'         => $this->getOauthLoginSource($client),
            'oAuthError'    => $request->get('oAuthError'),
            'autoLoginError'=> $request->get('autoLoginError')
        ));
    }

    private function getOauthLoginSource($client)
    {
        $clientErpId = $client->getErpId();
        return $this
        ->get('doctrine.orm.default_entity_manager')
        ->getRepository("DomainBundle:AdminOAuthLogin")
        ->findOneBy(['erpId' => $clientErpId]);
    }

    public function loginCheckAction(Request $request)
    {
        if ($request->isMethod('GET')) {
            return $this->redirectToRoute('redirect_admin_login');
        }
    }

    /**
     * @param Client $client
     * @param Request $request
     * @return PurchaseLoginDto
     */
    public function processTokenPurchaseLogin(Client $client, Request $request)
    {
        $loginStatus = new PurchaseLoginDto();

        if (!$this->isTokenPurchaseLogin($request)) {
            $loginStatus->setLoginSuccess(false);
            return $loginStatus;
        }

        $email = $request->get('email');
        $token = $request->get('token');
        $domain = $request->get('domain');

        if (!$this->isTokenValid($client, $token)) {
            $loginStatus->setLoginSuccess(false);
            return $loginStatus;
        }

        $userAdmin = $this->getUserAdmin($client, $email);

        if ($userAdmin && $client->getDomain() === $domain) {
            $this->doAutoLogin($userAdmin, $request);
            $loginStatus->setLoginSuccess(true);
        } else {
            $loginStatus->setLoginSuccess(false);
        }

        return $loginStatus;
    }

    /**
     * @param Client $client
     * @param Request $request
     * @return PurchaseLoginDto
     */
    private function processPurchaseLogin(Client $client, Request $request)
    {
        $loginStatus = new PurchaseLoginDto();

        if (!$this->isAutoPurchaseLogin($request)) {
            $loginStatus->setLoginSuccess(false);
            return $loginStatus;
        }

        $email          = $request->get('email');
        $domain         = $request->get('domain');
        $password       = $request->get('pwd');

        $user = $this->getUserAdmin($client, $email);
        if (!empty($user) && $this->isValidPassword($user, $password) && $client->getDomain() === $domain) {
            $this->doAutoLogin($user, $request);
            $loginStatus->setLoginSuccess(true);
        } else {
            $loginStatus->setLoginSuccess(false);
        }

        return $loginStatus;
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function isAutoPurchaseLogin(Request $request)
    {
        return $request->get('email') && $request->get('pwd') && $request->get('domain');
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function isTokenPurchaseLogin(Request $request)
    {
        return $request->get('email') && $request->get('token') && $request->get('domain');
    }

    private function isValidPassword(Users $user, $rawPassword)
    {
        $pwdTest = new MessageDigestPasswordEncoder();
        return $pwdTest->isPasswordValid($user->getPassword(), $rawPassword, $user->getSalt());
    }

    private function doAutoLogin(Users $user, Request $request)
    {
        $passwordToken = new UsernamePasswordToken($user, $user->getPassword(), "public", $user->getRoles());
        $this->get('security.token_storage')->setToken($passwordToken);
        $event = new InteractiveLoginEvent($request, $passwordToken);
        $this->get('event_dispatcher')->dispatch('security.interactive_login', $event);
    }

    /**
     * @param Client $client
     * @param string $email
     * @return Users | null
     */
    public function getUserAdmin(Client $client, $email)
    {
        $userRepository = $this->get('core.repository.user');

        return $userRepository->findOneBy([
            'username' => $email,
            'client' => $client,
        ]);
    }

    /**
     * @param Client $client
     * @param $token
     * @return bool
     */
    public function isTokenValid(Client $client, $token)
    {
        $clientRepository = $this
            ->get('doctrine.orm.default_entity_manager')
            ->getRepository('DomainBundle:Client');

        $tokenClient = $clientRepository->findOneBy([
            'changePlanHash' => $token,
            'id' => $client->getId()
        ]);

        return $tokenClient ? true : false;
    }


    private function loadClientModules(Client $client)
    {
        $moduleRepository =$this
            ->get('doctrine.orm.default_entity_manager')
            ->getRepository('DomainBundle:Module');

        return $moduleRepository->findClientModule($client);
    }

    private function loadClientCustomFields(Client $client)
    {
        $dm = $this->get("doctrine_mongodb")->getManager();
        $repo = $dm->getRepository("DomainBundle:CustomFields\Field");
        return $repo->loadCustomFieldName();
    }

    public function login2faAction(Request $request) {

		$session = $request->getSession();
		$faHelper = $this->container->get('wspot.2fa.helper');
		$securityContext = $this->container->get('security.token_storage');
		$key = $faHelper->getSessionKey($session);

		$user = $securityContext->getToken()->getUser();

		if ($session->get($key) === true)
		{
			return $this->redirectToRoute("admin_dashboard");
		}

		if ($request->getMethod() == 'POST')
		{
			//Check the authentication code
			if ($faHelper->checkCode($user, $request->get('_auth_code')) == true)
			{
				//Flag authentication complete
				$session->set($key, true);
				return $this->redirectToRoute("admin_dashboard");
			}
			else
			{
				$session->getFlashBag()->set("error", "The verification code is not valid.");
			}
		}

		return $this->render('AdminBundle:Login:login2fa.html.twig');
	}

    public function authenticateTokenAction(Request $request)
    {
        $session = $request->getSession();
        $session->set('wspotClient', null);
        $client = $this->get('core.service.client_selector')->define($request->getHost());
        $secretKey = $this->getParameter('cm_jwt_secret');
        $jwt = $request->query->get('token');
        $logger = $this->get('logger');

        if (!$jwt) {
            $error = "Não foi possível logar automaticamente, token não encontrado.";
            $logger->addCritical($error . " Painel: " . $client->getDomain());
            return new RedirectResponse($this->generateUrl('login_admin', ['autoLoginError' => $error]));
        }

        try {
            $decoded = JWT::decode($jwt, $secretKey, ['HS256']);
        } catch (\Exception $e) {
            $logger->addCritical("Erro ao decodificar jwt, painel: " . $client->getDomain());
            return new RedirectResponse($this->generateUrl('login_admin', ['autoLoginError' => "Não foi possível obter informações do token."]));
        }
        if ($client->getErpId() != $decoded->erp_id || !$decoded->erp_id || !$client->getErpId()) {
            $logger->addCritical(" Usuário " . $decoded->email . " não tem permissão para acessar " . $client->getDomain() . ", pois pertence a erp diferentes");
            return new RedirectResponse($this->generateUrl('login_admin', ['autoLoginError' => "Você não tem permissão para acessar o painel"]));
        }
        $host = $this->getParameter('business_manager_host');
        $curl = new GuzzleClient([
            'base_uri'      => $host . "/api/v3/",
            'http_errors'   => false,
            'timeout'       => 10,
            'headers'       => [
                'Content-Type' => 'application/json'
            ]
        ]);

        $response = $curl
        ->request(
            'GET',
            "check-spot/" . $client->getId() . "/org/" . $decoded->organization_id,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization'=> $jwt
                ]
            ]
        );

        $result = json_decode($response->getBody()->getContents(), true);
        $statusCode = $response->getStatusCode();

        if (!$result || $statusCode != 200) {
            $logger->addCritical(" Usuário " . $decoded->email . " não tem permissão para acessar " . $client->getDomain());
            return new RedirectResponse($this->generateUrl('login_admin', ['autoLoginError' => "Você não tem permissão para acessar o painel"]));
        }

        $userRepository = $this->get('core.repository.user');
        $user = $userRepository->findOneBy([
            'username' => $decoded->email,
            'erpId' => $decoded->erp_id,
            'createdAtOauth' => 0,
            'client' => null
        ]);
        $role = $decoded->wspot_rule;
        $roleFinded = null;
        $em = $this->container->get("doctrine.orm.entity_manager");
        $errorRoleNotFound = "Não foi possível logar automaticamente: Usuário recebido não pertence a nenhuma role do painel administrativo.";
        if ($role) {
            $roleFinded = $em
            ->getRepository('DomainBundle:Roles')
            ->findOneBy(['role' => $role]);
        } else {
            $logger->addCritical($errorRoleNotFound . " User: " . $decoded->email);
            return new RedirectResponse($this->generateUrl('login_admin', ['autoLoginError' => $errorRoleNotFound]));
        }
        if (!$roleFinded) {
            $logger->addCritical($errorRoleNotFound . " User: " . $decoded->email);
            return new RedirectResponse($this->generateUrl('login_admin', ['autoLoginError' => $errorRoleNotFound]));
        }
        if ($user) {
            $user->setRole($roleFinded); 
        } else {
            $newUser = new Users();
            $userService = $this->get('core.service.user');
            
            $newUser->setUsername($decoded->email);
            $newUser->setNome($decoded->first_name);
            $newUser->setStatus(Users::ACTIVE);
            $newUser->setReceiveReportMail(0);
            $newUser->setReportMailLanguage(0);
            $newUser->setFinancialManager(0);
            $newUser->setResetedToStrongPassword(1);
            $newUser->setRole($roleFinded);
            $newUser->setErpId($decoded->erp_id);
            $user = $userService->registerByAutoLogin($newUser, true);
        }
        $clientModules = $this->loadClientModules($client);
        $customFields = $this->loadClientCustomFields($client);

        $modulesShortCodes = [];
        $customFieldsIdentifiers = [];

        foreach ($clientModules as $module) {
            $modulesShortCodes[] = $module->getShortCode();
        }

        foreach ($customFields as $field ) {
            $customFieldsIdentifiers[] = $field->getIdentifier();
        }
        $session->set("fields", $customFieldsIdentifiers);
        $clientHost = $request->getHost();
        $isRegularDomain = (strpos($clientHost, 'wspot.com.br') || strpos($clientHost, 'mambowifi'));
        $session->set('isRegularDomain', $isRegularDomain);
        $session->set('modulesActive', $modulesShortCodes);
        $session->set("clientHost", $clientHost);
		$session->set("panel_access", "client_admin");
        
        $passwordToken = new UsernamePasswordToken($user, null, "admin_secured_area", $user->getRoles());
        $this->get('security.token_storage')->setToken($passwordToken);
        $event = new InteractiveLoginEvent($request, $passwordToken);
        $this->get('event_dispatcher')->dispatch('security.interactive_login', $event);

        return new RedirectResponse($this->generateUrl('admin_dashboard'));
    }

	public function exitAction(Request $request)
    {
        $authorizationToken = $this->container->get('core.service.authorization_token');
        /**
         * @var Users $user
         */
        $user = $this->getUser();

        if ($user->isSpotManager()){
            $redirect = $this->redirectToRoute("spots_manager_logout");
        }else{
            $redirect = $this->redirectToRoute("logout_admin");
        }

        $redirect = $authorizationToken->removeCookie($redirect, $request->getHost());
        return $redirect;
    }

	private function incrementLoginAttempts($username)
	{
		if (!$this->get('core.service.cache')->isActive()) {
			return;
		}

        $redisBlockKey = 'login_block_' . $username;
		$redisAttemptsKey = 'login_attempts_' . $username;

        $blockedRemainingTime = $this->get('core.service.cache')->getTTL($redisBlockKey);
		if($blockedRemainingTime > 0) {
			return;
		}

        $attempts = $this->get('core.service.cache')->get($redisAttemptsKey);
        if (!$attempts) {
            $attempts = 0;
        }
        $attempts++;

        if ($attempts > $this->maxAttempts) {
			$this->get('core.service.cache')->set($redisBlockKey, $attempts, $this->blockDuration);
            $this->get('core.service.cache')->set($redisAttemptsKey, $attempts, 3600);
            return;
		}

        $delay = pow(2, $attempts);
        $this->get('core.service.cache')->set($redisAttemptsKey, $attempts, 3600);
        $this->get('core.service.cache')->set($redisBlockKey, $attempts, $delay);
	}
}
