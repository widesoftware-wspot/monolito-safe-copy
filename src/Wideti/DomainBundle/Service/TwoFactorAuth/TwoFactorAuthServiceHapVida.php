<?php

namespace Wideti\DomainBundle\Service\TwoFactorAuth;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Exception\TwoFactorAuthConfigurationNotFoundException;
use Wideti\DomainBundle\Service\HttpRequest\HttpRequestService;
use Wideti\DomainBundle\Service\TwoFactorAuth\Dto\ResponseAuthorization;

class TwoFactorAuthServiceHapVida implements TwoFactorAuthService
{
	const SHORTCODE = 'hapvida';
	/**
	 * @var EntityManager
	 */
	private $entityManager;
    /**
     * @var HttpRequestService
     */
    private $httpRequestService;
	/**
	 * @var Session
	 */
	private $session;

	/**
	 * TwoFactorAuthServiceHapVida constructor.
	 * @param EntityManager $entityManager
	 * @param HttpRequestService $httpRequestService
	 * @param Session $session
	 */
	public function __construct(EntityManager $entityManager, HttpRequestService $httpRequestService, Session $session)
	{
		$this->entityManager        = $entityManager;
		$this->httpRequestService   = $httpRequestService;
		$this->session              = $session;
	}

    public function isModuleActive()
    {
    	$client = $this->session->get('wspotClient');

        $twoFactor = $this->entityManager
            ->getRepository('DomainBundle:TwoFactorAuth')
            ->findOneBy([
            	'client' => $client,
                'shortcode' => self::SHORTCODE
            ]);

        if (!$twoFactor) {
            return false;
        }

        return $twoFactor->isEnable();
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
        $message    = isset($response->getContent()->mensagem) ? $response->getContent()->mensagem : "";

        return new ResponseAuthorization(
            $response->getStatus() == 200,
            $message
        );
    }

	/**
	 * @param $shortcode
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
