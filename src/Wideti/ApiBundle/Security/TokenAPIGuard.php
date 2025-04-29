<?php

namespace Wideti\ApiBundle\Security;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractFormLoginAuthenticator;
use Wideti\ApiBundle\Controller\ApiResource;
use Wideti\DomainBundle\Dto\ApiLogDto;
use Wideti\DomainBundle\Entity\ApiWSpot;
use Wideti\DomainBundle\Entity\ApiWSpotResources;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Exception\Api\UnauthorizedResourceException;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearchAware;

class TokenAPIGuard extends AbstractFormLoginAuthenticator
{
    use ElasticSearchAware;

    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Client $client
     */
    private $client;

    /**
     * TokenAPIGuard constructor.
     * @param ContainerInterface $container
     * @param EntityManager $entityManager
     */
    public function __construct(ContainerInterface $container, EntityManager $entityManager)
    {
        $this->container = $container;
        $this->entityManager = $entityManager;
    }

    /**
     * Returns a response that directs the user to authenticate.
     *
     * This is called when an anonymous request accesses a resource that
     * requires authentication. The job of this method is to return some
     * response that "helps" the user start into the authentication process.
     *
     * Examples:
     *  A) For a form login, you might redirect to the login page
     *      return new RedirectResponse('/login');
     *  B) For an API token authentication system, you return a 401 response
     *      return new Response('Auth header required', 401);
     *
     * @param Request $request The request that resulted in an AuthenticationException
     * @param AuthenticationException $authException The exception that started the authentication process
     *
     * @return Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        throw new AuthenticationException();
    }

    /**
     * Get the authentication credentials from the request and return them
     * as any type (e.g. an associate array). If you return null, authentication
     * will be skipped.
     *
     * Whatever value you return here will be passed to getUser() and checkCredentials()
     *
     * For example, for a form login, you might:
     *
     *      return array(
     *          'username' => $request->request->get('_username'),
     *          'password' => $request->request->get('_password'),
     *      );
     *
     * Or for an API token that's on a header, you might use:
     *
     *      return array('api_key' => $request->headers->get('X-API-TOKEN'));
     *
     * @param Request $request
     *
     * @return mixed|null
     */
    public function getCredentials(Request $request)
    {
        $controller = explode(':', $request->attributes->get('_controller'));

        $url = $request->getHttpHost();
        $url = explode(".", $url);


        if ($url[1] == "wspot" || $url[1] == "mambowifi") {
            $domain = $url[0];
        } else {
            $domain = $request->getHost();
        }

        $this->client = $this->entityManager
            ->getRepository('Wideti\DomainBundle\Entity\Client')
            ->findOneBy(['domain' => $domain]);

        $token = $this->getToken($request);

        $credentials = [
            'token' => $token,
            'controller' => $controller[0],
            'action' => $controller[1],
            'method' => $request->getMethod(),
            'domain' => $domain,
            'route' => $request->get('_route')
        ];


        return $credentials;
    }

    /**
     * Return a UserInterface object based on the credentials.
     *
     * The *credentials* are the return value from getCredentials()
     *
     * You may throw an AuthenticationException if you wish. If you return
     * null, then a UsernameNotFoundException is thrown for you.
     *
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     *
     * @throws AuthenticationException
     *
     * @return UserInterface|null
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        /**
         * @var ApiWSpot $token
         */
        $token = $this->entityManager
            ->getRepository('Wideti\DomainBundle\Entity\ApiWSpot')
            ->findOneBy([
                'client' => $this->client,
                'token' => $credentials['token']
            ]);

        return $token;
    }

    /**
     * Throw an AuthenticationException if the credentials are invalid.
     *
     * The *credentials* are the return value from getCredentials()
     *
     * @param mixed $credentials
     * @param UserInterface $user
     *
     * @throws AuthenticationException
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        /**
         * @var ApiResource $apiResource
         */
        $apiResource        = $this->container->get($credentials['controller']);
        $resourceName       = $apiResource->getResourceName();
        $tokenPermissions   = $user->getResources();
        $isAuthorized       = false;
        $resources          = [];

        /**
         * @var ApiWSpotResources $permission
         */
        foreach ($tokenPermissions as $permission) {
            $resources[$permission->getResource()][] = $permission->getMethod();
        }

        if (array_key_exists($resourceName, $resources) &&
            in_array($credentials['method'], $resources[$resourceName])) {
            $isAuthorized = true;
        }

        $this->logRequest(
            $credentials['token'],
            $isAuthorized,
            $this->client->getId(),
            $credentials['controller'],
            $credentials['method'],
            $resourceName,
            $credentials['route']
        );

        if (!$isAuthorized) {
            throw new AuthenticationException();
        }
        return true;
    }

    /**
     * Called when authentication executed, but failed (e.g. wrong username password).
     *
     * This should return the Response sent back to the user, like a
     * RedirectResponse to the login page or a 403 response.
     *
     * If you return null, the request will continue, but the user will
     * not be authenticated. This is probably not what you want to do.
     *
     * @param Request $request
     * @param AuthenticationException $exception
     *
     * @return Response|null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        throw new UnauthorizedResourceException();
    }

    /**
     * Called when authentication executed and was successful!
     *
     * This should return the Response sent back to the user, like a
     * RedirectResponse to the last page they visited.
     *
     * If you return null, the current request will continue, and the user
     * will be authenticated. This makes sense, for example, with an API.
     *
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey The provider (i.e. firewall) key
     *
     * @return Response|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return null;
    }

    /**
     * Does this method support remember me cookies?
     *
     * Remember me cookie will be set if *all* of the following are met:
     *  A) This method returns true
     *  B) The remember_me key under your firewall is configured
     *  C) The "remember me" functionality is activated. This is usually
     *      done by having a _remember_me checkbox in your form, but
     *      can be configured by the "always_remember_me" and "remember_me_parameter"
     *      parameters under the "remember_me" firewall key
     *
     * @return bool
     */
    public function supportsRememberMe()
    {
        return false;
    }

    private function getToken(Request $request)
    {
        $token = $request->get('token');

        if (!$token) {
            $token = $request->headers->get('X-TOKEN');
        }

        return $token;
    }

    private function logRequest($apiKey, $isAuthorized, $clientId, $controller, $httpMethod, $resourceName, $route)
    {
        $now = new \DateTime();
        $now = $now->format("Y-m-d H:i:s");

        $log = new ApiLogDto();
        $log->setApiKey($apiKey);
        $log->setAuthorized($isAuthorized);
        $log->setClientId($clientId);
        $log->setController($controller);
        $log->setDate($now);
        $log->setMethod($httpMethod);
        $log->setResourceName($resourceName);
        $log->setRoute($route);

        $index = $this->container->getParameter('elastic_api_log_index');
        $this->elasticSearchService->index("request", json_encode($log), null, $index);
    }
}
