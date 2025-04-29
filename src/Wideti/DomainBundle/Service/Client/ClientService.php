<?php
namespace Wideti\DomainBundle\Service\Client;

use Doctrine\DBAL\DBALException;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\OptimisticLockException;
use mysql_xdevapi\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Wideti\DomainBundle\Entity\AccessCodeSettings;
use Wideti\DomainBundle\Entity\AccessPointsGroups;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\LegalKinds;
use Wideti\DomainBundle\Entity\Module;
use Wideti\DomainBundle\Entity\ModuleConfigurationValue;
use Wideti\DomainBundle\Entity\Plan;
use Wideti\DomainBundle\Entity\WhiteLabel;
use Wideti\DomainBundle\Exception\ClientNotFoundException;
use Wideti\DomainBundle\Exception\DuplicateDomainException;
use Wideti\DomainBundle\Exception\NasWrongParametersException;
use Wideti\DomainBundle\Gateways\Consents\ListSignatureGateway;
use Wideti\DomainBundle\Gateways\Consents\PostConsentGateway;
use Wideti\DomainBundle\Helpers\ClientHelper;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\RadacctRepositoryAware;
use Wideti\DomainBundle\Service\AccessCode\AccessCodeService;
use Wideti\DomainBundle\Service\AccessCode\AccessCodeServiceImp;
use Wideti\DomainBundle\Service\AccessPointsGroups\AccessPointsGroupsServiceAware;
use Wideti\DomainBundle\Service\ApiWSpot\ApiWSpotService;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\ClientLogs\ClientLogsService;
use Wideti\DomainBundle\Service\ClientLogs\Dto\ClientLogDto;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Configuration\ConfigurationServiceAware;
use Wideti\DomainBundle\Service\LegalBaseManager\LegalBaseManagerService;
use Wideti\DomainBundle\Service\Mail\MailHeaderServiceAware;
use Wideti\DomainBundle\Service\Mailer\MailerServiceAware;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessageBuilder;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\DomainBundle\Service\RDStation\RDStationAware;
use Wideti\DomainBundle\Service\RDStation\RDStationService;
use Wideti\DomainBundle\Service\Erp\ErpService;
use Wideti\DomainBundle\Service\Template\TemplateAware;
use Wideti\DomainBundle\Service\User\UserServiceAware;
use Wideti\DomainBundle\Service\WhiteLabel\WhiteLabelService;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Symfony\Component\Yaml\Parser;
use Wideti\DomainBundle\Entity\Template;
use Wideti\DomainBundle\Entity\Users;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\PasswordServiceAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class ClientService
{
    const DEFAULT_SMS_COST = '0,13';

    use SecurityAware;
    use EntityManagerAware;
    use MongoAware;
    use TemplateAware;
    use PasswordServiceAware;
    use RadacctRepositoryAware;
    use UserServiceAware;
    use AccessPointsGroupsServiceAware;
    use RDStationAware;
    use ConfigurationServiceAware;
    use LoggerAware;
    use MailerServiceAware;
    use MailHeaderServiceAware;
    use TwigAware;
    use SessionAware;
    use ModuleAware;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var DocumentManager
     */
    protected $dm;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var CacheServiceImp
     */
    private $cacheService;
    /**
     * @var ErpService
     */
    private $erpService;
    /**
     * @var ClientLogsService
     */
    private $clientLogsService;
    /**
     * @var ApiWSpotService
     */
    private $apiWSpotService;
	/**
	 * @var WhiteLabelService
	 */
	private $whiteLabelService;

	private $smsCost;
    /**
     * @var AccessCodeServiceImp
     */
    private $accessCodeService;

    /**
     * @var PostConsentGateway
     */
    private $postConsentGateway;

    /**
     * @var LegalBaseManagerService
     */
    private $legalBaseManagerService;

    /**
     * ClientService constructor.
     * @param ConfigurationService $configurationService
     * @param CacheServiceImp $cacheService
     * @param ErpService $erpService
     * @param ClientLogsService $clientLogsService
     * @param ApiWSpotService $apiWSpotService
     * @param WhiteLabelService $whiteLabelService
     * @param AccessCodeServiceImp $accessCodeService
     * @param $smsCost
     * @param PostConsentGateway $postConsentGateway
     */
    public function __construct(
        ConfigurationService $configurationService,
        CacheServiceImp $cacheService,
        ErpService $erpService,
        ClientLogsService $clientLogsService,
        ApiWSpotService $apiWSpotService,
        WhiteLabelService $whiteLabelService,
        AccessCodeServiceImp $accessCodeService,
        $smsCost,
        PostConsentGateway $postConsentGateway,
        LegalBaseManagerService $legalBaseManagerService
    ) {
        $this->configurationService         = $configurationService;
        $this->cacheService                 = $cacheService;
        $this->erpService                   = $erpService;
        $this->clientLogsService            = $clientLogsService;
        $this->apiWSpotService              = $apiWSpotService;
        $this->whiteLabelService            = $whiteLabelService;
        $this->accessCodeService            = $accessCodeService;
        $this->smsCost                      = $smsCost;
        $this->postConsentGateway           = $postConsentGateway;
        $this->legalBaseManagerService      = $legalBaseManagerService;
    }

    /**
     * @param $data
     * @return JsonResponse
     */
    public function trial(array $data)
    {
        try {
            $client = new Client();
            $client->setCreatedBy(Client::CREATED_BY_AUTO_HIRING);
            $client->setStatus(Client::STATUS_POC);
            $client->setPocEndDate(new \DateTime('+7 days'));
            $this->createByApi($client, $data);
        }catch (DuplicateDomainException $e){
            $this->logger->addWarning("domínio já registrado");
            return new JsonResponse([
                "status" => 409,
                "err_info"=> "Conflito. Erro ao criar o spot",
                "message"=> "Não foi possível criar o spot com o domínio {$data['domain']}",
            ], 409);
        } catch (\Exception $ex) {
            $this->logger->addCritical("Falha ao criar o cliente {$data['domain']}: {$ex->getMessage()}");
            return new JsonResponse([
                "status"    => 500,
                "message"   => "Falha ao criar o cliente {$data['domain']}"
            ], 500);
        }

        return new JsonResponse([
            "status"    => 200,
            "message"   => "Cliente {$data['domain']} criado com sucesso"
        ]);
    }

    /**
     * @param array $data
     * @return JsonResponse
     */
    public function purchase(array $data, $traceHeaders = [])
    {
	    if (isset($data['changePlan']) && $data['changePlan'] == true) {
	    	return $this->changePlan($data);
	    }
        try {
            $client = new Client();
            $client->setCreatedBy(Client::CREATED_BY_AUTO_HIRING);
            $client->setStatus(Client::STATUS_ACTIVE);
            if(isset($data['erpError']) && $data['erpError'] == true){
                $client->setStatus(Client::STATUS_POC);
                $client->setPocEndDate(new \DateTime('+7 days'));
            }
            if(isset($data['status']) && $data['status'] == 2){
                $client->setStatus(Client::STATUS_POC);
                $client->setPocEndDate(new \DateTime('+8 days'));
            }
            $disable_password_authentication = false;
            if (isset($data['type_ap'])) {
                $typeAp = (int) $data['type_ap'];
                if ($typeAp === 0 || $typeAp === 1) {
                    $authenticationType = ($typeAp === 1) ? "enable_password_authentication" : "disable_password_authentication";
                    $client->setAuthenticationType($authenticationType);
                    $disable_password_authentication = $authenticationType === "disable_password_authentication";
                } else {
                    $this->logger->addWarning("type_ap inválido: " . $typeAp );
                    return new JsonResponse([
                        "status" => 400,
                        "err_info"=> "type_ap inválido",
                        "message"=> "Type_ap precisa ser 1 (com senha) ou 0 (sem senha).",
                    ], 409);
                }
            }
            $this->createByApi($client, $data, $traceHeaders);
            if ($disable_password_authentication) {
                $this->configurationService->updateKey('login_form', false, $client);
            }
            if (key_exists('boletoUrl', $data)) {
                $this->sendBoletoNotification($data);
            }

        } catch (DuplicateDomainException $e){
            $this->logger->addWarning("domínio já registrado");
            return new JsonResponse([
                "status" => 409,
                "err_info"=> "Conflito. Erro ao criar o spot",
                "message"=> "Não foi possível criar o spot com o domínio {$data['domain']}",
            ], 409);
        } catch (\Exception $ex) {
            $this->logger->addCritical("Falha ao criar o cliente {$data['domain']}: {$ex->getMessage()}");
            return new JsonResponse([
                "status"  => 500,
                "message" => "Falha ao criar o cliente {$data['domain']}"
            ], 500);
        }

        return new JsonResponse([
            "status"  => 200,
            "message" => "Cliente {$data['domain']} criado com sucesso",
            "clientId" => $client->getId()
        ]);
    }

    /**
     * @param array $data
     * @return JsonResponse
     */
    public function vinculateerpclient(array $data)
    {
        try {
            /**
             * @var Client $client
             */
            $client = $this->em
                ->getRepository('DomainBundle:Client')
                ->findOneBy([
                    'domain' => $data['domain']
                ]);

            if (!$client) {
                throw new ClientNotFoundException("Client with domain [".$data['domain']."] not found.");
            }

            $client->setErpId($data['erpId']);
            $client->setClosingDate($data['due_date']);

            $changeLogMessage = $this->generateChangeLogMessage($client);
            $message = "Cliente com informações cadastrais alteradas.<br/>" . $changeLogMessage;
            $clientLog = new ClientLogDto();
            $clientLog
                ->setClientId($client->getId())
                ->setResponse('Sucesso')
                ->setAction($message)
                ->setAuthor($this->getUser()->getUsername())
                ->setDate(date('Y-m-d H:i:s'));

            $this->saveClient($client);
            $this->clientLogsService->log($clientLog);
        } catch (\Exception $ex) {
            $this->logger->addCritical("Falha ao vincular o cliente {$data['domain']} com o erp: {$ex->getMessage()}");
            return new JsonResponse([
                "status"  => 500,
                "message" => "Falha ao alterar o cliente {$data['domain']} com o erp"
            ], 500);
        }

        return new JsonResponse([
            "status"  => 200,
            "message" => "Cliente {$data['domain']} vinculado com sucesso"
        ]);
    }


    public function changePlan(array $data)
    {
        try {

            $client = $this->getClient($data);
            $this->changeStatus($data, $client);
            $this->completeRegistration($data, $client);

            if (key_exists('boletoUrl', $data)) {
                $this->sendBoletoNotification($data);
            }
        } catch (\Exception $ex) {
            $this->logger->addCritical("Falha ao migrar o plano do cliente {$data['domain']}: {$ex->getMessage()}");
            return new JsonResponse([
                "status"  => 500,
                "message" => "Falha ao migrar o plano do cliente {$data['domain']}"
            ], 500);
        }

        return new JsonResponse([
            "status"  => 200,
            "message" => "Plano do cliente {$data['domain']} alterado com sucesso"
        ]);
    }

    public function completeRegistration(array $data, Client $client)
    {
        $client->setDocument($data['company']['document']);

        $client = $this->populateAddressToClient($client, $data);

        $client->setContractedAccessPoints(array_key_exists('accessPoints', $data) ? $data['accessPoints'] : 1);

        $this->update($client);

        if (key_exists('boletoUrl', $data)) {
            $this->sendBoletoNotification($data);
            }
    }

    public function changeStatus(array $data, Client $client)
    {
        $client->setErpId(array_key_exists('erpId', $data) ? $data['erpId'] : null);
        $client->setStatus(Client::STATUS_ACTIVE);

        $client->setClosingDate(array_key_exists('erpDueDate', $data) ? $data['erpDueDate'] : null);
        $client->setPocEndDate(null);

        $this->update($client);
    }

    public function getClient(array $data){
        /**
         * @var Client $client
         */
        $client = $this->em
            ->getRepository('DomainBundle:Client')
            ->findOneBy([
                'domain' => $data['domain']
            ]);

        if (!$client) {
            throw new ClientNotFoundException();
        }

        return $client;
    }

	private function populateAddressToClient(Client $client, $data)
	{
		$address    = $data['company']['address'];
		$zipCode    = array_key_exists('zipCode', $address) ? $address['zipCode'] : '';
		$street     = array_key_exists('street', $address) ? $address['street'] : '';
		$number     = array_key_exists('number', $address) ? $address['number'] : '';
		$complement = array_key_exists('complement', $address) ? $address['complement'] : '';
		$district   = array_key_exists('district', $address) ? $address['district'] : '';
		$city       = array_key_exists('city', $address) ? $address['city'] : '';
		$state      = array_key_exists('state', $address) ? $address['state'] : '';

		$client->setZipCode($zipCode);
		$client->setAddress($street);
		$client->setAddressNumber($number);
		$client->setAddressComplement($complement);
		$client->setDistrict($district);
		$client->setCity($city);
		$client->setState($state);

		return $client;
	}

    /**
     * @param Client $client
     * @param $data
     * @param $traceHeaders
     * @return void
     * @throws DBALException
     * @throws DuplicateDomainException
     * @throws OptimisticLockException
     * @throws \MongoCursorException
     * @throws \MongoCursorTimeoutException
     * @throws \MongoException
     * @throws \Wideti\DomainBundle\Exception\SendEmailFailException
     */
	private function createByApi(Client $client, $data, $traceHeaders = [])
    {
        $adminUsers = [];
        if (array_key_exists('admin', $data)) {
            $adminUsers = [
                [
                    'name'              => $data['admin']['fullName'],
                    'email'             => $data['admin']['email'],
                    'password'          => $data['admin']['password'],
                    'financialManager'  => true
                ]
            ];
        }
        if (array_key_exists('payment', $data) && array_key_exists('financialEmail', $data['payment'])) {
            array_push($adminUsers, [
                'name'              => $data['company']['name'],
                'email'             => $data['payment']['financialEmail'],
                'password'          => false,
                'financialManager'  => true
            ]);
        }

        $jobArea =  'Outros';
        if (array_key_exists('admin', $data)) {
            $jobArea =  array_key_exists('jobArea', $data['admin']) ? $data['admin']['jobArea'] : 'Outros';
        }
        $segment = $this->em->getRepository('DomainBundle:Segment')->findOneBy(['name' => $jobArea]);

	    $client->setSegment($segment);
	    $client->setErpId(array_key_exists('erpId', $data) ? $data['erpId'] : null);
	    $client->setType(Client::TYPE_SIMPLE);

        $domain = $data['domain'];

        $isMamboDomain = strpos($domain, ".mambowifi.com");
        $isWspotDomain = strpos($domain, ".wspot.com.br");

        if($isMamboDomain || $isWspotDomain) {
            $domainSplit = explode('.', $domain);
            $domain = $domainSplit[0];
        }

        $validateClient = $this->em->getRepository("DomainBundle:Client")->findOneBy(['domain'=>$domain]);
        if ($validateClient) {
            throw new DuplicateDomainException();
        }


	    $client->setDomain($domain);
	    $client->setCompany($data['company']['name']);

	    if ($client->getStatus() !== Client::STATUS_POC) {
		    $client->setDocument($data['company']['document']);
		    $client = $this->populateAddressToClient($client, $data);
	    }

	    $client->setSmsCost($this->smsCost);
	    $client->setContractedAccessPoints(array_key_exists('accessPoints', $data) ? $data['accessPoints'] : 1);
        $client->setClosingDate(array_key_exists('erpDueDate', $data) ? $data['erpDueDate'] : null);
        try {
            $additionalInfo = [
                'from_email'            => isset($data['guest_email_sender']) ? $data['guest_email_sender'] : $adminUsers[0]['email'],
                'redirect_url'          => array_key_exists('redirectUrl', $data) ? $data['redirectUrl'] : 'https://www.google.com',
                'partner_name'          => $data['company']['name'],
                'modules'               => array_key_exists('modules', $data)? $data['modules'] : [],
                'email_sender_default'  => $data['emailSenderDefault'],
            ];
        } catch (\Exception $e) {
            $this->logger->addCritical("Fail to get params for create a new spot in request for client {$client->getDomain()}. ({$e})");
        }
        if (array_key_exists('whitelabel', $data)) {
            try {
                $additionalInfo['whitelabel'] = [
                        'company_name' => $data['whitelabel']['company_name'],
                        'panel_color' => $data['whitelabel']['panel_color'],
                        'logotipo' => $data['whitelabel']['logotipo'],
                        'signature' => $data['whitelabel']['signature'],
                ];
            } catch (\Exception $e) {
                $this->logger->addCritical("Fail to get params for create a new spot whitelabel in request for client {$client->getDomain()}. ({$e})");
            }
        }
        $this->create($client, $additionalInfo, $traceHeaders);

        $this->setDefaultModules($client, $additionalInfo);

        $this->createAdminUser($client, $adminUsers);

        if ($client->getStatus() === Client::STATUS_POC) {
            $this->sendSalesTeamNotification($client);
        }
    }

    /**
     * @param Client $client
     * @throws DBALException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     * @throws \MongoCursorException
     * @throws \MongoCursorTimeoutException
     * @throws \MongoException
     */
    public function create(Client $client, $aditionalInfo=[], $traceHeaders = [])
    {
        $client = $this->setDefaultValuesIfEmpty($client);
        $client->setDomain(strtolower($client->getDomain()));
        $isWhiteLabel = $this->isAWhiteLabelDomain($client->getDomain());
        if ($isWhiteLabel) {
            $client->setIsWhiteLabel(true);
            $client->setEmailSenderDefault($aditionalInfo['email_sender_default']);
            $client->setMongoDatabaseName(StringHelper::slugDomain($client->getDomain()));

            $default_sender_email = array_unique([
                $aditionalInfo['email_sender_default'],
                $aditionalInfo['from_email']
            ]);
            foreach ($default_sender_email as $email) {
                $this->verifyEmailAddress($email);
            }
        } else {
            $client->setIsWhiteLabel(false);
            $client->setMongoDatabaseName($client->getDomain());
            $client->setEmailSenderDefault('no-reply@mambowifi.com');
        }

        $proPlan = $this->em->getRepository('DomainBundle:Plan')->findOneBy(['shortCode' => Plan::PRO]);

        if (!$proPlan) {
            $this->logger->addCritical("Pro plan not found on client create", ["clientDomain" => $client->getDomain()]);
        }

	    if (!$client->getPlan()) {
		    $client->setPlan($proPlan);
	    }

	    $otherSegment = $this->em->getRepository('DomainBundle:Segment')
		    ->findOneBy(['name' => 'Outros']);

	    if (!$client->getSegment()) {
		    $client->setSegment($otherSegment);
	    }

	    $this->client = $client;

        $this->em->getConnection()->beginTransaction();
        $whitelabelIdentity = (array_key_exists('whitelabel', $aditionalInfo)) ?  $aditionalInfo['whitelabel'] : [];

        try {
            $this->createTemplate();
            $this->createDefaultAccessPointGroup($aditionalInfo);
            $this->createWhiteLabel($whitelabelIdentity);
            $this->createGuestGroups();
            $this->createGuestIndexes($client);
            $this->createAccessCodeSettings($client);
            $this->em->persist($this->client);
            $this->em->flush();

            $this->setModuleConfigurations($client, 'create');
        } catch (DBALException $e) {
            $this->em->getConnection()->rollBack();
            throw new DBALException($e->getMessage());
        }

        $this->em->getConnection()->commit();

        $clientLog = new ClientLogDto();
        $clientLog
            ->setClientId($client->getId())
            ->setAuthor(ClientLogDto::ORIGIN_PANEL_WSPOT)
            ->setAction(ClientLogDto::ACTION_CREATE_CLIENT)
            ->setResponse('Sucesso')
            ->setDate(date('Y-m-d H:i:s'));
        $this->clientLogsService->log($clientLog);

        if ($client->getNoRegisterFields()) {
            $legalKind = LegalKinds::LEGITIMO_INTERESSE;
        } else {
            $legalKind = LegalKinds::TERMO_CONSENTIMENTO;
        }

        try {
            $this->legalBaseManagerService->defineLegalBase($client, $legalKind);
            if ($legalKind == LegalKinds::TERMO_CONSENTIMENTO) {
                $this->createDefaultTerm($client, $traceHeaders);
            }
        }catch (\Exception $e){
            $this->logger->addCritical("Fail to create default consent term for client {$client->getDomain()}");
        }
    }

    private function createTemplate()
    {
        $template = new Template();
        $template->setName('Template Padrão');
        $template->setClient($this->client);

        $this->templateService->create($template);
    }

    private function createDefaultAccessPointGroup($aditionalInfo)
    {
        $accessPointGroup = new AccessPointsGroups();
        $accessPointGroup->setIsDefault(true);
        $accessPointGroup->setGroupName('Grupo padrão');
        $accessPointGroup->setClient($this->client);

        $this->accessPointsGroupsService->create($accessPointGroup,false, $aditionalInfo);
    }

    private function createWhiteLabel($whitelabelIdentity=[])
    {
    	$this->whiteLabelService->setDefault($this->client, $whitelabelIdentity);
    }

    /**
     * @throws \MongoCursorException
     * @throws \MongoCursorTimeoutException
     * @throws \MongoException
     */
    public function createGuestGroups()
    {
        $mongoClient = $this->dm
            ->getConnection()
            ->getMongoClient();

        $domain = $this->client->getDomain();
        if(!strpos($domain, "wspot.com.br") || !strpos($domain,"mambowifi")) {
            $domain = StringHelper::slugDomain($this->client->getDomain());
        }

        $database   = $mongoClient->{$domain};
        $database->dropCollection('groups');
        $collection = $database->groups;
        $parser = new Parser();

        $groups = $parser->parse(
            file_get_contents(__DIR__ . '/../../DataFixtures/ODM/Test/Fixtures/SampleGuestsGroups.yml')
        );

        $groupConfigItems = $parser->parse(
            file_get_contents(__DIR__ . '/../../DataFixtures/ODM/Test/Fixtures/SampleGuestsGroupsConfiguration.yml')
        );

        $groupConfigValues = $parser->parse(
            file_get_contents(__DIR__ . '/../../DataFixtures/ODM/Test/Fixtures/SampleGuestsGroupsConfigurationValue.yml')
        );

        $groupConfigs = [];

        $control = 0;
        foreach ($groupConfigItems['Wideti\DomainBundle\Document\Group\Configuration'] as $items) {
            foreach ($groupConfigValues['Wideti\DomainBundle\Document\Group\ConfigurationValue'] as $key => $row) {
                if (in_array('@'.$key, $items['configurationValues'])) {
                    array_push($items['configurationValues'], $row);
                }
            }

            foreach ($items['configurationValues'] as $i => $value) {
                if (is_string($value)) {
                    array_shift($items['configurationValues']);
                }
            }
            if ($control > 2) {
                break;
            }
            array_push($groupConfigs, $items);
            $control++;
        }
        $guestGroup = [];
        foreach ($groups['Wideti\DomainBundle\Document\Group\Group'] as $group) {
            $group['configurations'] = $groupConfigs;
            array_push($guestGroup, $group);
        }


        foreach ($guestGroup as $group) {
            $collection->insert($group);
        }
    }

    /**
     * @param Client $client
     * @throws \Exception
     */
    public function createGuestIndexes(Client $client)
    {
        $domain = $this->client->getDomain();
        if(!strpos($domain, "wspot.com.br")|| !strpos($domain, "mambowifi")) {
            $domain = StringHelper::slugDomain($this->client->getDomain());
        }

        $collection = $this
            ->dm
            ->getConnection()
            ->getMongoClient()
            ->selectDB($domain)
            ->selectCollection('guests');

        $collection->createIndex(['mysql' => 1]);
    }

    private function createAccessCodeSettings(Client $client)
    {
        $this->accessCodeService->createDefaultSettings($client);
    }

    /**
     * @param Client $client
     * @param $action
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function setModuleConfigurations(Client $client, $action)
    {
        if ($action == 'create') {
            $enableAccessCode    = 0;
            $enableBusinessHours = 0;

            $accessCode = $this->em
                ->getRepository('DomainBundle:ModuleConfiguration')
                ->findOneByKey('enable_access_code');

            $enableModule = new ModuleConfigurationValue();
            $enableModule->setClient($client);
            $enableModule->setItems($accessCode);
            $enableModule->setValue($enableAccessCode);
            $this->em->persist($enableModule);

            $businessHours = $this->em
                ->getRepository('DomainBundle:ModuleConfiguration')
                ->findOneByKey('enable_business_hours');

            $enableModule = new ModuleConfigurationValue();
            $enableModule->setClient($client);
            $enableModule->setItems($businessHours);
            $enableModule->setValue($enableBusinessHours);
            $this->em->persist($enableModule);
        }

        $modules = [];

        foreach ($client->getModules() as $module) {
            $shortCode = $module->getShortCode();
            array_push($modules, $shortCode);
        }

        if (in_array('segmentation', $modules)) {
            $this->apiWSpotService->createSegmentationTokenViaBluePanel($client);
        }

        if (in_array('deskbee_integration', $modules)) {
            $this->createModuleConfigurationValues($client, 'enable_deskbee_integration', '');
            $this->createModuleConfigurationValues($client, 'deskbee_client_id', '');
            $this->createModuleConfigurationValues($client, 'deskbee_client_secret', '');
            $this->createModuleConfigurationValues($client, 'deskbee_redirect_url', '');
            $this->createModuleConfigurationValues($client, 'deskbee_environment', 'dev');
            $this->em->flush();
        }

        if (in_array('hubsoft_integration', $modules)) {
            $this->createModuleConfigurationValues($client, 'enable_hubsoft_prospecting', '1');
            $this->createModuleConfigurationValues($client, 'enable_hubsoft_authentication', '1');
            $this->createModuleConfigurationValues($client, 'enable_hubsoft_integration', '1');
            $this->createModuleConfigurationValues($client, 'hubsoft_client_id', '');
            $this->createModuleConfigurationValues($client, 'hubsoft_client_secret', '');
            $this->createModuleConfigurationValues($client, 'hubsoft_username', '');
            $this->createModuleConfigurationValues($client, 'hubsoft_password', '');
            $this->createModuleConfigurationValues($client, 'hubsoft_host', 'https://api.demo.hubsoft.com.br');
            $this->createModuleConfigurationValues($client, 'hubsoft_client_group', '');
            $this->createModuleConfigurationValues($client, 'hubsoft_id_service', '');
            $this->createModuleConfigurationValues($client, 'hubsoft_id_origin', '');
            $this->createModuleConfigurationValues($client, 'hubsoft_id_crm', '');
            $this->createModuleConfigurationValues($client, 'hubsoft_auth_button', 'Sou cliente');

            $this->em->flush();
        }

        if (in_array('Ixc_integration', $modules)) {
            $this->createModuleConfigurationValues($client, 'enable_Ixc_prospecting', '1');
            $this->createModuleConfigurationValues($client, 'enable_Ixc_authentication', '1');
            $this->createModuleConfigurationValues($client, 'enable_Ixc_integration', '1');
            $this->createModuleConfigurationValues($client, 'Ixc_client_secret', '123:98765432109876543210987654321');
            $this->createModuleConfigurationValues($client, 'Ixc_host', 'https://demo.ixcsoft.com.br/adm.php');
            $this->createModuleConfigurationValues($client, 'Ixc_client_group', '');
            $this->createModuleConfigurationValues($client, 'Ixc_auth_button', 'Sou cliente PROVEDOR X');
            $this->em->flush();
            $IxcIntegration  = $this->moduleService->checkModuleIsActive('Ixc_integration', $client);



        }

        if ($action == 'update') {
            $accessCode     = $this->moduleService->checkModuleIsActive('access_code', $client);
            $businessHours  = $this->moduleService->checkModuleIsActive('business_hours', $client);
            $deskbeeIntegration  = $this->moduleService->checkModuleIsActive('deskbee_integration', $client);
            $hubsoftIntegration  = $this->moduleService->checkModuleIsActive('hubsoft_integration', $client);
            $IxcIntegration  = $this->moduleService->checkModuleIsActive('Ixc_integration', $client);


            $enableAccessCode    = (in_array('access_code', $modules) && $accessCode) ? '1' : '';
            $enableBusinessHours = (in_array('business_hours', $modules) && $businessHours) ? '1' : '';
            $enableDeskbee       = (in_array('deskbee_integration', $modules) && $deskbeeIntegration) ? '1' : '';
            $enableHubsoft       = (in_array('hubsoft_integration', $modules) && $hubsoftIntegration) ? '1' : '';
            $enableIxc           = (in_array('Ixc_integration', $modules) && $IxcIntegration) ? '1' : '';

            $this->updateModuleStatus('enable_access_code', $enableAccessCode, $client);
            $this->updateModuleStatus('enable_business_hours', $enableBusinessHours, $client);
            $this->updateModuleStatus('enable_deskbee_integration', $enableDeskbee, $client);
            $this->updateModuleStatus('enable_hubsoft_authentication', $enableHubsoft, $client);
            $this->updateModuleStatus('enable_hubsoft_integration', $enableHubsoft, $client);
            $this->updateModuleStatus('enable_hubsoft_prospecting', $enableHubsoft, $client);

            $this->updateModuleStatus('enable_Ixc_authentication', $enableIxc, $client);
            $this->updateModuleStatus('enable_Ixc_integration', $enableIxc, $client);
            $this->updateModuleStatus('enable_Ixc_prospecting', $enableIxc, $client);
        }

        $this->em->flush();
    }


    /**
     * @param Client $client
     * @param string $configuration
     */
    public function createModuleConfigurationValues($client, $configuration, $value) {
        $deskbeeConfigurationValue = $this->em
        ->getRepository('DomainBundle:ModuleConfigurationValue')
        ->findByModuleConfigurationKey($client, $configuration);


        if (!$deskbeeConfigurationValue) {
            $deskbeeConfiguration = $this->em
                ->getRepository('DomainBundle:ModuleConfiguration')
                ->findOneByKey($configuration);
            $deskbeeConfigurationValue = new ModuleConfigurationValue();
            $deskbeeConfigurationValue->setClient($client);
            $deskbeeConfigurationValue->setItems($deskbeeConfiguration);
            $deskbeeConfigurationValue->setValue($value);
            $this->em->persist($deskbeeConfigurationValue);
        }
    }

    /**
     * @param $configKeyName
     * @param $status
     * @param Client $client
     */
    public function updateModuleStatus($configKeyName, $status, Client $client)
    {
        $module = $this->em
            ->getRepository('DomainBundle:ModuleConfiguration')
            ->findOneByKey($configKeyName);

        $enableModule = $this->em
            ->getRepository('DomainBundle:ModuleConfigurationValue')
            ->findOneBy([
                'client' => $client,
                'items'  => $module
            ]);

        if (empty($enableModule)) {
            $enableModule = new ModuleConfigurationValue();
            $enableModule->setClient($client);
            $enableModule->setItems($module);
        }

        $enableModule->setValue($status);
        $this->em->persist($enableModule);
    }

    /**
     * @param Client $client
     * @param $users
     * @return bool
     * @throws DBALException
     * @throws \Exception
     */
    private function createAdminUser(Client $client, $users)
    {
        foreach ($users as $data) {
            $user   = new Users();
            $role   = $this->em
                ->getRepository('DomainBundle:Roles')
                ->find(Users::ROLE_ADMIN);

            $user->setUsername(trim($data['email']));
            $user->setNome(trim($data['name']));
            $user->setStatus(Users::ACTIVE);
            $user->setReceiveReportMail(true);
            $user->setReportMailLanguage(0);
            $user->setFinancialManager($data['financialManager']);
            $user->setRole($role);
            $user->setClient($client);
            $user->setTwoFactorAuthenticationEnabled(false);

            $autoPassword = true;
            if ($data['password']) {
                $user->setPassword($data['password']);
                $autoPassword = false;
            }

            $this->userService->registerByAutoHiring($user, $autoPassword, $client);
        }

        return true;
    }

    /**
     * @param Client $client
     * @param $data
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function setDefaultModules(Client $client, $data)
    {
        $modulesDefault = $this->defineDefaultModules($data);
        $modules = $this->em
            ->getRepository('DomainBundle:Module')
            ->getDefaultModules($modulesDefault);

        foreach ($modules as $module) {
            $client->addModule($module);
        }
        $this->em->persist($client);
        $this->em->flush();

    }

    private function defineDefaultModules($data) {
        if (!isset($data["modules"]) || empty($data["modules"])) {
            return [
                'campaign',
                'access_code',
                'blacklist',
                'business_hours',
                'api',
                'customer_area',
                'rd_station',
                'egoi',
                'sms_marketing',
                'survey'
            ];
        }
        return $data["modules"];
    }

    /**
     * @param Client $client
     * @throws \Wideti\DomainBundle\Exception\SendEmailFailException
     */
    private function sendSalesTeamNotification(Client $client)
    {
        $builder = new MailMessageBuilder();
        $message = $builder
            ->subject('Nova POC Criada - '. $client->getCompany())
            ->from(['API WSpot' => $this->emailHeader->getSender()])
            ->to($this->emailHeader->getCommercialRecipient())
            ->htmlMessage(
                $this->renderView(
                    'ApiBundle:Client:emailNovoCliente.html.twig',
                    [
                        'config'    => [
                            'partner_name' => 'WSpot'
                        ],
                        'client'    => "{$client->getDomain()}.mambowifi.com",
                        'panel'     => "http://demo.wspot.com.br/panel/client/{$client->getId()}/edit/"
                    ]
                )
            )
            ->build()
        ;

        $this->mailerService->send($message);
    }

    private function generateChangeLogMessage(Client $client)
    {
        $uow = $this->em->getUnitOfWork();
        $uow->computeChangeSets();
        $changeset = $uow->getEntityChangeSet($client);

        $message = "";
        foreach ($changeset  as $key => $value) {
            $item     = ClientHelper::translateFields($key);
            $oldValue = $value[0];
            $newValue = $value[1];

            if ($oldValue instanceof \DateTime) {
                $oldValue = $oldValue->format('Y-m-d H:i:s');
            }

            if ($newValue instanceof \DateTime) {
                $newValue = $newValue->format('Y-m-d H:i:s');
            }

            $message .= "<b>{$item}</b> - <b>De:</b> {$oldValue} <b>Para:</b> {$newValue}<br/>";
        }
        return $message;
    }

    public function saveClient(Client $client)
    {
        $this->em->persist($client);
        $this->em->flush();
    }

    private function isWhiteLabel(Client $client)
    {
        /** @var Module $m */
        foreach ($client->getModules() as $m){
            if ($m->getShortCode() == 'white_label') return true;
        }
        return $this->isAWhiteLabelDomain($client->getDomain());
    }

    /**
     * @param Client $client
     */
    public function update(Client $client)
    {
        $changeLogMessage = $this->generateChangeLogMessage($client);
        $this->em->persist($client);
        $this->em->flush();
        $clientLog = new ClientLogDto();

        $user = !is_null($this->getUser())? $this->getUser()->getUsername() : "System";
        $isWhiteLabel = $this->isWhiteLabel($client);

        if ($isWhiteLabel) {
            $client->setIsWhiteLabel(true);
            $client->setMongoDatabaseName(StringHelper::slugDomain($client->getDomain()));
        } else {
            $client->setIsWhiteLabel(false);
            $client->setMongoDatabaseName($client->getDomain());
            $client->setEmailSenderDefault('no-reply@mambowifi.com');
        }

        $clientLog
            ->setClientId($client->getId())
            ->setAuthor($user)
            ->setDate(date('Y-m-d H:i:s'));

        try {
            $this->saveClient($client);
            $this->setModuleConfigurations($client, 'update');
            $message = "Cliente com informações cadastrais alteradas.<br/>" . $changeLogMessage;
            $clientLog
                ->setResponse('Sucesso')
                ->setAction($message);
            $this->clientLogsService->log($clientLog);
        } catch(OptimisticLockException  $e){
            $message = "Falha ao alterar informações cadastrais do cliente.<br/>" . $changeLogMessage;
            $clientLog
                ->setResponse('Falha')
                ->setAction($message);
            $this->clientLogsService->log($clientLog);
            $this->logger->addCritical("OptimisticLock during persist client {$client->getDomain()}");
            throw $e;
        } catch (\Exception $e) {
            $message = "Falha ao alterar informações cadastrais do cliente.<br/>" . $changeLogMessage;
            $clientLog
                ->setResponse('Falha')
                ->setAction($message);
            $this->clientLogsService->log($clientLog);
            $this->logger
                ->addCritical("Error during persist client {$client->getDomain()} with message {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * @param $clientId
     * @return null|Client
     */
    public function getClientById($clientId)
    {
        $repository = $this->em->getRepository('DomainBundle:Client');
        return $repository->findOneBy([
            'id' => $clientId
        ]);
    }

    /**
     * @param $erpId
     * @return null|object|Client
     */
    public function getClientByErpId($erpId)
    {
        $repository = $this->em->getRepository('DomainBundle:Client');
        return $repository->findOneBy([
            'erpId' => $erpId
        ]);
    }

    /**
     * @param $domain
     * @return Client|null
     */
    public function getClientByDomain($domain)
    {
        $repository = $this->em->getRepository('DomainBundle:Client');
        return $repository->findOneBy([
            'domain' => $domain
        ]);
    }

    /**
     * @param $hash
     * @return array
     */
    public function getClientInformationByHash($hash)
    {
        $repository = $this->em->getRepository('DomainBundle:Client');
        return $repository->getClientByHash($hash);
    }

    /**
     * @param Client $client
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function doneFirstConfiguration(Client $client)
    {
        $client->setInitialSetup(true);
        $this->em->merge($client);
        $this->em->flush();

        $nas    = $this->session->get(Nas::NAS_SESSION_KEY);
        $client = $this->getLoggedClient();

        try {
            $emailNotAllowed = [
                Users::USER_DEFAULT,
                Users::USER_ADMIN
            ];

            $email = ($this->configurationService->get($nas, $client, 'from_email')) ?: $this->getUser()->getUsername();

            if ($client->getCreatedBy() != Client::CREATED_BY_PANEL && in_array($email, $emailNotAllowed) === false) {
                $this->rdStationService->insertTagLead($email, [RDStationService::TAG_ACESSOU_PAINEL_WSPOT]);
            }
        } catch (\Exception $ex) {
            $this->logger->addError("Fail to add tag on RDStation (acessou_painel_wspot) - {$ex->getMessage()}");
        }
    }

    private function sendBoletoNotification($data)
    {
	    $recipients     = [];
    	$adminEmail     = $data['admin']['email'];
    	$financialEmail = $data['payment']['financialEmail'];

	    array_push($recipients, [
		    $data['admin']['fullName'] => $adminEmail
	    ]);

    	if ($adminEmail !== $financialEmail) {
		    array_push($recipients, [
			    $data['admin']['fullName'] => $financialEmail
		    ]);
	    }

        $builder = new MailMessageBuilder();
        $message = $builder
            ->subject('Boleto Mensalidade Mambo WiFi')
            ->from(['WSpot' => $this->emailHeader->getSender()])
            ->to($recipients)
            ->htmlMessage(
                $this->renderView(
                    'ApiBundle:Client:boleto.html.twig',
                    [
                        'config'    => [
                            'partner_name' => 'Mambo WiFi'
                        ],
                        'data' => $data
                    ]
                )
            )
            ->build()
        ;

        $this->mailerService->send($message);
    }

    /**
     * @param Client $client
     * @return null|object
     */
    public function refreshEntity(Client $client)
    {
        return $this
            ->em
            ->getRepository('DomainBundle:Client')
            ->findOneBy([
                'id' => $client->getId()
            ]);
    }

    /**
     * @param Client $client
     * @return null|Client
     */
    private function setDefaultValuesIfEmpty(Client $client)
    {
        if (!$client) return null;

        if (!$client->getSmsCost()) {
            $client->setSmsCost(self::DEFAULT_SMS_COST);
        }

        if (!$client->getContractedAccessPoints()) {
            $client->setContractedAccessPoints('1');
        }

        if (!$client->getClosingDate()) {
            $client->setClosingDate('10');
        }

        return $client;
    }

    /**
     * @param $client
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setClientStatusActive($client)
    {
        $client->setStatus(Client::STATUS_ACTIVE);
        $this->em->persist($client);
        $this->em->flush();

        $clientLog = new ClientLogDto();
        $clientLog
            ->setClientId($client->getId())
            ->setAuthor($this->getUser()->getUsername())
            ->setAction(ClientLogDto::ACTION_ACTIVATING_CLIENT)
            ->setResponse('Sucesso')
            ->setDate(date('Y-m-d H:i:s'));
        $this->clientLogsService->log($clientLog);
    }

    /**
     * @param $client
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setClientStatusInactive($client)
    {
        $client->setStatus(Client::STATUS_INACTIVE);
        $this->em->persist($client);
        $this->em->flush();

        $clientLog = new ClientLogDto();
        $clientLog
            ->setClientId($client->getId())
            ->setAuthor($this->getUser()->getUsername())
            ->setAction(ClientLogDto::ACTION_INACTIVATING_CLIENT)
            ->setResponse('Sucesso')
            ->setDate(date('Y-m-d H:i:s'));
        $this->clientLogsService->log($clientLog);
    }

    /**
     * Requests verification of an identity email for whitelabel panels in Amazon SES.
     *
     * Sends a request to verify an identity email in Amazon SES. After the request is sent,
     * the owner of the email address will receive a verification email with a link to click
     * and confirm ownership of the email address.
     *
     * @param $emailAddress
     * @throws \Aws\Exception\AwsException
     */
    public function verifyEmailAddress($emailAddress)
    {
        try {
            $result = $this->mailerService->validateSender($emailAddress);
        } catch (\Aws\Exception\AwsException $ex) {
            throw new \Aws\Exception\AwsException('Failed to verify email address');
        }
    }

    /**
     * @param DocumentManager $dm
     */
    public function setDocumentManager(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * @param Client $client
     * @param $erpData
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function syncData(Client $client, $erpData)
    {
        $company    = $erpData['st_nome_sac'];
        $document   = $erpData['st_cgc_sac'];
        $address    = $erpData['st_endereco_sac'];
        $addrNumber = $erpData['st_numero_sac'];
        $addrCompl  = $erpData['st_complemento_sac'];
        $district   = $erpData['st_bairro_sac'];
        $city       = $erpData['st_cidade_sac'];
        $state      = $erpData['st_estado_sac'];
        $zipCode    = $erpData['st_cep_sac'];

        $client->setCompany($company);
        $client->setDocument($document);
        $client->setAddress($address);
        $client->setAddressNumber($addrNumber);
        $client->setAddressComplement($addrCompl);
        $client->setDistrict($district);
        $client->setCity($city);
        $client->setState($state);
        $client->setZipCode($zipCode);

        $this->em->persist($client);
        $this->em->flush();
    }

	/**
	 * @param Client $client
	 * @param array $headers
	 */
    public function createDefaultTerm(Client $client, $headers = []) {
        $consentList = [
            ['id'=>'2a94be7b-db96-4240-a9db-8f321c297f62'],
            ['id'=>'53dd6be3-8acc-4de4-884c-01b6447c5ac6'],
            ['id'=>'778d2ff8-54a4-4647-9334-8a4ec25dc3b1'],
            ['id'=>'adfdacec-6d28-48b3-afc2-de462701d9b0'],
        ];
        $this->postConsentGateway->post($client, $consentList, 'pt_BR', $headers);
    }

    private function isAWhiteLabelDomain($clientDomain)
    {
        if (strpos($clientDomain, '.')) {
            return true;
        }
        return false;
    }
}
