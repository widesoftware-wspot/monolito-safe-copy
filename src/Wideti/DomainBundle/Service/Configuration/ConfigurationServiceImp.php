<?php
namespace Wideti\DomainBundle\Service\Configuration;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Yaml\Parser;
use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Exception\ConfigurationNotFoundException;
use Wideti\DomainBundle\Exception\DatabaseException;
use Wideti\DomainBundle\Repository\ConfigurationRepository;
use Wideti\DomainBundle\Service\AccessPointsGroupsConfiguration\AccessPointsGroupsConfigurationServiceImp;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\Dto\ConfigurationDto;
use Wideti\DomainBundle\Service\Configuration\Dto\FacebookConfigurationDto;
use Wideti\DomainBundle\Service\GuestDevices\GuestDevices;
use Wideti\DomainBundle\Service\Radcheck\RadcheckService;
use Wideti\DomainBundle\Service\Sms\SmsVerification;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Doctrine\ORM\NoResultException;

class ConfigurationServiceImp implements ConfigurationService
{
    const CONFIGS_NO_REGISTER_FIELDS = [
        'login_form' => false,
        'auto_login' => false,
        'facebook_login' => false,
        'google_login' => false,
        'linkedin_login' => false,
        'unique_device_per_mac' => "0",
        'signup_form' => true
    ];
    use SecurityAware;

    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var DocumentManager
     */
    private $mongo;
	/**
	 * @var ConfigurationRepository
	 */
	private $configurationRepository;
    /**
     * @var CacheServiceImp
     */
    private $cacheService;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var RadcheckService
     */
    private $radcheckService;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var SmsVerification
     */
    private $smsVerification;
    /**
     * @var string
     */
    private $googleClientId;
    /**
     * @var string
     */
    private $googleClientSecret;
    /**
     * @var string
     */
    private $googleCallbackUrl;
    /**
     * @var AccessPointsGroupsConfigurationServiceImp $accessPointsGroupsConfigurationServiceImp
     */
    private $accessPointsGroupsConfigurationServiceImp;
    /**
     * @var GuestDevices
     */
    private $guestDevices;


    /**
     * ConfigurationServiceImp constructor.
     * @param EntityManager $em
     * @param DocumentManager $mongo
     * @param ConfigurationRepository $configurationRepository
     * @param CacheServiceImp $cacheService
     * @param Session $session
     * @param RadcheckService $radcheckService
     * @param Logger $logger
     * @param SmsVerification $smsVerification
     * @param $googleClientId
     * @param $googleClientSecret
     * @param $googleCallbackUrl
     * @param AccessPointsGroupsConfigurationServiceImp $accessPointsGroupsConfigurationServiceImp
     * @param GuestDevices $guestDevices
     */
    public function __construct(
        EntityManager $em,
        DocumentManager $mongo,
        ConfigurationRepository $configurationRepository,
        CacheServiceImp $cacheService,
        Session $session,
        RadcheckService $radcheckService,
        Logger $logger,
        SmsVerification $smsVerification,
        $googleClientId,
        $googleClientSecret,
        $googleCallbackUrl,
        AccessPointsGroupsConfigurationServiceImp $accessPointsGroupsConfigurationServiceImp,
        GuestDevices $guestDevices

    ) {
        $this->em                       = $em;
        $this->mongo                    = $mongo;
	    $this->configurationRepository  = $configurationRepository;
	    $this->cacheService             = $cacheService;
	    $this->session                  = $session;
	    $this->radcheckService          = $radcheckService;
	    $this->logger                   = $logger;
	    $this->smsVerification          = $smsVerification;
	    $this->googleClientId           = $googleClientId;
	    $this->googleClientSecret       = $googleClientSecret;
	    $this->googleCallbackUrl        = $googleCallbackUrl;
	    $this->accessPointsGroupsConfigurationServiceImp = $accessPointsGroupsConfigurationServiceImp;
        $this->guestDevices             = $guestDevices;
    }

	public function getGroups($groupIdParent)
	{
		$results = $this->configurationRepository->getGroups();

		$groups = [];

		foreach ($results as $result) {
			array_push($groups, $result['group_short_code']);
		}

		return $groups;
	}

	/**
	 * @param $configuration
	 * @param $domain
	 * @return array
	 */
    public function getConfigAsMap($configuration, $domain)
    {
	    $map = [];

	    /**
	     * @var ConfigurationDto $item
	     */
	    foreach ($configuration as $item) {
		    $map[$item->getKey()] = $item->getValue();
	    }

	    $map['aws_folder_name'] = $domain;

	    return $map;
    }

	public function getConfigByKey(Client $client, $key)
	{
		return $this->configurationRepository->getDefaultConfigByKey($client, $key);
	}

	public function getByGroupId($groupId)
	{
		return $this->configurationRepository->getByGroupId($groupId);
	}

    /**
     * @param Nas|null $nas
     * @param Client $client
     * @return array|mixed
     * @throws \Exception
     */
    public function getConfigurationMap(Nas $nas = null, Client $client)
    {
        $cacheKey = $this->getCacheKey($nas->getAccessPointMacAddress());

        if ($this->cacheService->isActive() && $this->cacheService->exists($cacheKey)) {
            return $this->cacheService->get($cacheKey);
        }

        $configMap = $this->getByIdentifierOrDefault($nas->getAccessPointMacAddress(), $client);

        $this->setOnSession($cacheKey, $configMap);

        return $configMap;
    }

	/**
	 * @param array $items
	 * @param AccessPointsGroups $accessPointsGroups
	 * @throws \Doctrine\DBAL\DBALException
	 */
    public function saveConfiguration(array $items, AccessPointsGroups $accessPointsGroups)
    {
		foreach ($items as $item) {
			if ($item->getkey() == 'confirmation_email' || $item->getkey() == 'enable_confirmation') {
                if ($item->getValue() === false) {
                    $this->deleteExpiration($accessPointsGroups->getClient());
                }
            }

            if (!$item->getValue()) {
                $item->setValue('');
            }

            if ($this->smsVerification->checkLimitSendSms()
                && $item->getKey() == 'confirmation_sms' && $item->getValue() == 1) {
                $item->setValue(0);
            }

            if ($this->smsVerification->checkLimitSendSms() && $item->getKey() == 'enable_welcome_sms') {
                $item->setValue(0);
            }

			$this->configurationRepository->update($item);
		}
        $this->em->flush();

        if ($this->cacheService->isActive()) {
            $this->cacheService->removeAllConfigs();
        }
    }

    public function deleteExpiration(Client $client)
    {
        try {
            return $this->radcheckService->removeAllExpirationTime($client);
        } catch (\Exception $e) {
            $this->logger->addCritical($e->getMessage());
        }
    }

    public function deleteExpirationByGuestGroup($clientId, $groupId)
    {
        try {
            $this->radcheckService->removeAllExpirationTimeByGuest($clientId, $groupId);
        } catch (\Exception $e) {
            $this->logger->addCritical($e->getMessage() . "\n Com a stack: "
                . $e->getTrace());
            throw new \Exception("Erro ao deletar Dados da Radcheck em deleteExpirationByGuestGroup(), erro: "
                . $e->getMessage());
        }
    }

    public function updateKey($key, $value, Client $client)
    {
    	$this->configurationRepository->updateByKey($client, $key, $value);

        if ($this->cacheService->isActive()) {
            $this->cacheService->removeAllConfigs();
        }
    }

    public function updateNoRegisterFieldsConfigKeys(Client $client) {
        $configsNoRegisterFields = $this::CONFIGS_NO_REGISTER_FIELDS;
        foreach ($configsNoRegisterFields as $key => $value) {
            $this->updateKey($key, $value, $client);
        }
    }

    public function get(Nas $nas = null, $client, $key)
    {
        $identifier = ($nas) ? $nas->getAccessPointMacAddress() : null;
        $configMap  = $this->getByIdentifierOrDefault($identifier, $client);

        if (!$configMap) {
            throw new ConfigurationNotFoundException("Configuration \"{$key}\" not found on session.");
        }

        return $configMap[$key];
    }

    /**
     * @param $identifier
     * @param string $prefix
     * @return string
     */
    public function getCacheKey($identifier, $prefix = "")
    {
        $key = "config_{$identifier}";
        $key = empty($prefix) ? $key : "{$prefix}_{$key}";
        return $key;
    }

    public function createDefaultConfiguration(Client $client, $group = null, $customItems = null,$aditionalInfo = [])
    {
	    $hasDefault = $this->configurationRepository->findBy([
		    'client'    => $client,
	    	'isDefault' => true
	    ]);

	    $isDefault = $hasDefault ? false : true;

        $parser = new Parser();

	    $configurations = $parser->parse(
		    file_get_contents(__DIR__ . '/../../DataFixtures/ORM/Fixtures/Default/configuration.yml')
	    );

	    if (strpos($client->getDomain(), ".") && (!strpos($client->getDomain(), "wspot.com.br") || !strpos($client->getDomain(), "mambowifi"))) {
            $configurations = $parser->parse(
                file_get_contents(__DIR__ . '/../../DataFixtures/ORM/Fixtures/Default/configuration_white_label.yml')
            );
        }

        if (!$group) {
            $group = $this->em
                ->getRepository('DomainBundle:AccessPointsGroups')
                ->findOneBy([
                    'client'    => $client,
                    'isDefault' => true
                ]);
        }

        $items = [];

        foreach ($configurations['Wideti\DomainBundle\Entity\Configuration'] as $config) {

            $configsNoRegisterFields = $this::CONFIGS_NO_REGISTER_FIELDS;
            if ($client->getNoRegisterFields() && array_key_exists($config['key'], $configsNoRegisterFields)) {
                $config['value'] = $configsNoRegisterFields[$config['key']];
            }

            if ($config['key'] == 'aws_folder_name') {
                $config['value'] = $client->getDomain();
            }

            if ($config['key'] == 'partner_name') {
                $config['value'] = isset($aditionalInfo['partner_name']) ? $aditionalInfo['partner_name'] : 'Empresa';
            }

            if ($config['key'] == 'redirect_url') {
                $config['value'] = isset($aditionalInfo['redirect_url']) ? $aditionalInfo['redirect_url'] : 'https://www.google.com';
            }

            if ($config['key'] == 'from_email') {
                $config['value'] = isset($aditionalInfo['from_email']) ? $aditionalInfo['from_email'] : '';
            }

	        if ($customItems) {
		        foreach ($customItems as $item) {
			        if ($config['key'] == $item['key']) {
				        $config['value'] = $item['value'];
			        }
		        }
	        }

            array_push($items, $config);
        }

        $configuration = [
        	'client'            => $client->getId(),
            'isDefault'         => $isDefault,
            'accessPointGroup'  => $group->getId(),
            'items'             => $items
        ];

        $this->accessPointsGroupsConfigurationServiceImp->persistAccessPointsGroupsConfigurations($group,
            $configuration,
            $client);
    }

    public function removeAllButDefaults(Client $client)
    {
    	return $this->configurationRepository->removeAllButDefaults($client);
    }

    public function removeByGroup(Client $client, $groupId)
    {
	    return $this->configurationRepository->removeByGroup($client, $groupId);
    }

    /**
     * @param string $apIdentifier
     * @param Client $client
     * @return array
     * @throws \Exception
     */
    public function getByIdentifierOrDefault($apIdentifier, Client $client)
    {
        $ap = $this->em->getRepository('DomainBundle:AccessPoints')
            ->findOneBy([
                'identifier' => $apIdentifier,
                'client' => $client
            ]);

        if ($ap) {
            $idGroup = $this->getParentConfigurationByGroup($ap->getGroup());
            $parentAccessPointGroup = $this->em
                ->getRepository('DomainBundle:AccessPointsGroups')
                ->findOneBy([
                    'id' => $idGroup
                ]);

	            return $this->getByAccessPointGroup($parentAccessPointGroup);
        }

        return $this->getDefaultConfiguration($client);
    }

    public function getParentConfigurationByGroup(AccessPointsGroups $apg)
    {
        $parentConfiguration = $apg->getParentConfigurations();
        if ($parentConfiguration === true) {

            $parentAccessPointGroup = $this->em
                ->getRepository('DomainBundle:AccessPointsGroups')
                ->findOneBy([
                    'id' => trim($apg->getParent())
                ]);

            if (!$parentAccessPointGroup) {
                throw new \Exception("Configurações do access points não foi encontrado");
            }

            $id = $this->getParentConfigurationByGroup($parentAccessPointGroup);
        } else {
            return $apg->getId();
        }

        return $id;
    }

    /**
     * @param AccessPointsGroups $accessPointsGroups
     * @return array
     * @throws \Exception
     */
    public function getByAccessPointGroup(AccessPointsGroups $accessPointsGroups)
    {
	    $client         = $accessPointsGroups->getClient();
	    $configurations = $this->configurationRepository->getByGroupId($accessPointsGroups->getId());
        $configMap      = $this->getConfigAsMap($configurations, $client->getDomain());
        return $configMap;
    }

    /**
     * @param Client $client
     * @return array
     * @throws \Exception
     */
    public function getDefaultConfiguration(Client $client)
    {
        $groupDefault = $this->em
            ->getRepository('DomainBundle:AccessPointsGroups')
            ->findOneBy([
                'client' => $client,
                'isDefault' => true
            ]);

        return $this->getByAccessPointGroup($groupDefault);
    }

    public function setOnSession($key, $configMap)
    {
        if ($this->cacheService->isActive()) {
            $this->cacheService->set($key, $configMap);
        } else {
            $this->session->set($key, $configMap);
        }
    }

    /**
     * @param $mac
     * @return bool
     */
    public function isMacAlreadyRegistered($guestMacAddress)
    {
        $client = $this->session->get('wspotClient');
        return (boolean) $this->guestDevices->getGuestsByMacDevice($client, $guestMacAddress);
    }

	/**
	 * @param $mac
	 * @return bool
	 * @throws \Doctrine\DBAL\DBALException
	 */
    public function isUniqueMacEnabled(Client $client, $mac)
    {
        $accessPoint = $this->em->getRepository('DomainBundle:AccessPoints')
            ->findOneBy([
                'client' => $client,
                'identifier' => $mac
            ]);

        if (is_null($accessPoint) || empty($accessPoint)) {
            return false;
        }

        $groupId = $accessPoint->getGroup()->getId();

        $config = $this->configurationRepository->getByKeyAndGroups($client, $groupId, 'unique_device_per_mac');

	    if (($config['value'] == "1" || $config['value'] == true)) {
            return true;
        }

        return false;
    }

    /**
     * @param Nas|null $nas
     * @param Client $client
     * @return FacebookConfigurationDto
     * @throws ConfigurationNotFoundException
     */
    public function getFacebookConfiguration(Nas $nas = null, Client $client)
    {
        $facebookConfig = new FacebookConfigurationDto();
        $facebookConfig
            ->setShare($this->get($nas, $client, 'facebook_share'))
            ->setLike($this->get($nas, $client, 'facebook_like'))
            ->setLikeUrl($this->get($nas, $client, 'facebook_like_url'))
            ->setShareUrl($this->get($nas, $client, 'facebook_share_url'))
            ->setShareHashtag($this->get($nas, $client, 'facebook_share_hashtag'))
            ->setShareMessage($this->get($nas, $client, 'facebook_share_message'));

        return $facebookConfig;
    }

    /**
     * @param $domainOfloggedClient
     * @return \Google_Client
     */
    public function getGoogleClient($domainOfloggedClient = null)
    {
        $client = new \Google_Client();
        $client->setClientId($this->googleClientId);
        $client->setClientSecret($this->googleClientSecret);
        $client->setScopes([
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/plus.login',
            'https://www.googleapis.com/auth/userinfo.profile'
        ]);
        $client->setState($domainOfloggedClient);
        $client->setRedirectUri($this->googleCallbackUrl);
        $client->setIncludeGrantedScopes(true);

        return $client;
    }

    public function getApGroupId(Nas $nas = null, $client)
    {
        try {
                $apIdentifier = ($nas) ? $nas->getAccessPointMacAddress() : null;

                $ap = $this->em->getRepository('DomainBundle:AccessPoints')
                    ->createQueryBuilder('ap')
                    ->select('IDENTITY(ap.group) AS groupId')  // Usando IDENTITY para obter o identificador da relação
                    ->where('ap.identifier = :identifier')
                    ->andWhere('ap.client = :client')
                    ->setParameter('identifier', $apIdentifier)
                    ->setParameter('client', $client)
                    ->getQuery()
                    ->getSingleScalarResult();

                return [(int)$ap];
            } catch (NoResultException $e) {
                //retornar um array com um valor de Id inexistente para não quebrar o formulario de cadastro
                // mas não irá exibir o campo relacionado ao groupId desejado caso nao localize um $ap ou um $apIdentifiery
                return [0];
            }
    }
}
