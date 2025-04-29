<?php


namespace Wideti\DomainBundle\Service\Auth\AuthorizationToken;


use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Roles;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Entity\UsersTokensAuth;
use Wideti\DomainBundle\Service\Module\ModuleService;

class AuthorizationToken implements AuthorizationTokenInterface
{
    const MICROSERVICES_AUTH_KEY = '_mauth';
    /**
     * @var Session
     */
    private $session;

    /**
     * @var ModuleService
     */
    private $moduleService;

    private $jwtSignature;

    public function __construct(ModuleService $moduleService, Session $session, $jwtSignature)
    {
        $this->moduleService = $moduleService;
        $this->session       = $session;
        $this->jwtSignature  = $jwtSignature;
    }

    /**
     * @param Users $user
     * @return Users
     */
    public function create(Users $user)
    {
        $jwt = $this->generateToken($user);
        $userToken = $user->getUserTokenAuth();
        if (is_null($userToken)){
            $userToken = UsersTokensAuth::build($user);
        }
        $userToken->setToken($jwt);
        $user->setUserTokenAuth($userToken);
        return $user;
    }

    private function generateToken(Users $user)
    {
        /**
         * @var Client $client
         */
        $client = $this->session->get('wspotClient');
        $listModules = $this->moduleService->getClientModules();

        $created = new \DateTime();
        $expirated = clone $created;
        $expirated->add(new \DateInterval("PT1H"));

        $payload = [
            "iss"        => $_SERVER["HTTP_HOST"],
            "user_id"    => $user->getId(),
            "client_id"  => $client->getId(),
            "iat"        => $created->getTimestamp(),
            "exp"        => $expirated->getTimestamp(),
            "modules"    => $listModules,
            "amp"        => base64_encode($user->getUsername())
        ];

        return JWT::encode($payload, $this->jwtSignature, 'HS256');
    }

    public function saveOnSession($token)
    {
        $this->session->set(self::MICROSERVICES_AUTH_KEY, $token);
    }

    public function saveOnCookie(Request $request, Response $response)
    {
        if (!$request->cookies->has(self::MICROSERVICES_AUTH_KEY)){
            $token = $this->session->get(self::MICROSERVICES_AUTH_KEY);
            $domain = $request->getHost();
            $expires = new \DateTime();
            $expires->add(new \DateInterval("PT1H"));
            $cookie = new Cookie(
                self::MICROSERVICES_AUTH_KEY,
                $token,
                $expires,
                "/",
                $domain,
                true,
                true
            );
            $response->headers->setCookie($cookie);
        }
    }

    public function removeCookie(RedirectResponse $response, $host)
    {
        $response->headers->clearCookie(
            AuthorizationToken::MICROSERVICES_AUTH_KEY,
            "/",
            $host,
            true,
            true
        );
        return $response;
    }
}