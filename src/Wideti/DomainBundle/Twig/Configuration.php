<?php

namespace Wideti\DomainBundle\Twig;

use Symfony\Component\HttpFoundation\Session\Session;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;

class Configuration extends \Twig_Extension
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var Session
     */
    private $session;

    public function __construct(ConfigurationService $configurationService, Session $session)
    {
        $this->configurationService = $configurationService;
        $this->session = $session;
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('config', [$this, 'getConfig'])
        ];
    }

    public function getConfig($registrationMacAddress, $key)
    {
        $configs = $this->configurationService
            ->getByIdentifierOrDefault($registrationMacAddress, $this->session->get('wspotClient'));

        return $configs[$key];
    }

    public function getName()
    {
        return 'config';
    }
}
