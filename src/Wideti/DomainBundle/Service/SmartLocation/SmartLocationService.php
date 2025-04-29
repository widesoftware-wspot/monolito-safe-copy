<?php

namespace Wideti\DomainBundle\Service\SmartLocation;

use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client as GuzzleClient;


class SmartLocationService
{
    use EntityManagerAware;
    use TwigAware;
    use SecurityAware;
    use FlashMessageAware;
    use ModuleAware;
    use LoggerAware;


    private $guzzleClient;

    /**
     * SmartLocationService constructor.
     */
    public function __construct() {
        $this->guzzleClientConfig = [
            'base_uri' => 'https://reports-analytics.datawifi.co',
            'defaults' => [
                'exceptions' => false,
            ],
            'headers' => ['Content-Type' => 'application/json']
        ];
    }

    public function LoginAction()
    {
        if (!$this->moduleService->modulePermission('smart_location')) {
            return false;
        }

        $client = $this->getLoggedClient();
        $this->guzzleClient = new GuzzleClient($this->guzzleClientConfig);
        $entity = $this->em
            ->getRepository('DomainBundle:SmartLocationCredentials')
            ->findOneBy([
                'client' => $client
            ]);

        $body = [
            "accountName" => $entity->getAccountName(),
            "customerId" => $entity->getCustomerId(),
            "password" => $entity->getPassword(),
        ];
        try {
            return $this->guzzleClient
                ->post("/auth/analytics-login", [
                  "body" => json_encode($body),
                  "timeout" => 3
                ]
            );
        }catch (RequestException $ex){
            $this->logger->addCritical($ex->getMessage());
        }
    }
}