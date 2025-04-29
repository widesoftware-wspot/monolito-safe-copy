<?php

namespace Wideti\DomainBundle\Service\TwoFactorAuth;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Exception\TwoFactorAuthConfigurationNotFoundException;
use Wideti\DomainBundle\Service\AccessCode\AccessCodeService;
use Wideti\DomainBundle\Service\HttpRequest\HttpRequestService;
use Wideti\DomainBundle\Service\Module\ModuleService;
use Wideti\DomainBundle\Service\TwoFactorAuth\Dto\ResponseAuthorization;

class TwoFactorAuthServiceAccessCode implements TwoFactorAuthService
{
	const SHORTCODE = 'access_code';

	/**
	 * @var EntityManager
	 */
	private $entityManager;
    /**
     * @var HttpRequestService
     */
    private $httpRequestService;

    /**
     * @var AccessCodeService
     */
    private $accessCodeService;

    /**
     * @var ModuleService
     */
    private $moduleService;
	/**
	 * @var Session
	 */
	private $session;

	/**
	 * TwoFactorAuthServiceAccessCode constructor.
	 * @param EntityManager $entityManager
	 * @param ModuleService $moduleService
	 * @param HttpRequestService $httpRequestService
	 * @param AccessCodeService $accessCodeService
	 * @param Session $session
	 */
	public function __construct(
    	EntityManager $entityManager,
        ModuleService $moduleService,
        HttpRequestService $httpRequestService,
        AccessCodeService $accessCodeService,
		Session $session
    ) {
		$this->entityManager        = $entityManager;
		$this->httpRequestService   = $httpRequestService;
		$this->accessCodeService    = $accessCodeService;
		$this->moduleService        = $moduleService;
		$this->session              = $session;
	}

    public function isModuleActive()
    {
        return $this->moduleService->checkModuleIsActive('access_code');
    }

    /**
     * @param $value
     * @return ResponseAuthorization
     * @throws TwoFactorAuthConfigurationNotFoundException
     */
    public function isAuthorized($value)
    {
	    $client = $this->session->get('wspotClient');

        $twoFactorAuth = $this->entityManager
            ->getRepository('DomainBundle:TwoFactorAuth')
            ->findOneBy([
            	'client'    => $client,
                'shortcode' => self::SHORTCODE
            ]);

        if (!$twoFactorAuth) {
            throw new TwoFactorAuthConfigurationNotFoundException();
        }

        $endpoint   = str_replace('{value}', $value, $twoFactorAuth->getEndpoint());
        $headers    = $twoFactorAuth->getHttpHeaders() ?: [];
        $response   = $this->httpRequestService->get($endpoint, $headers);
        $message    = isset($response->getContent()->message) ? $response->getContent()->message : "";

        return new ResponseAuthorization(
            $response->getStatus() == 200,
            $message
        );
    }

	/**
	 * @param Client $shortcode
	 * @return mixed|object|\Wideti\DomainBundle\Entity\TwoFactorAuth|null
	 */
    public function getTwoFactorAuthObject($shortcode)
    {
	    $client = $this->session->get('wspotClient');

        return $twoFactorAuth = $this->entityManager
            ->getRepository('DomainBundle:TwoFactorAuth')
            ->findOneBy([
            	'client'    => $client,
                'shortcode' => $shortcode
            ]);
    }
}
