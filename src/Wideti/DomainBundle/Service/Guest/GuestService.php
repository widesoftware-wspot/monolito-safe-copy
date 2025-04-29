<?php
namespace Wideti\DomainBundle\Service\Guest;

use Aws\Sns\Exception\NotFoundException;
use DateTime;
use Egulias\EmailValidator\EmailValidator;
use Elasticsearch\Common\Exceptions\Forbidden403Exception;
use MongoDate;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wideti\DomainBundle\Document\Group\Configuration;
use Wideti\DomainBundle\Document\Group\Group;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Document\Guest\Social;
use Wideti\DomainBundle\Document\Repository\GuestRepository;
use Wideti\DomainBundle\Dto\RestPaginateDto;
use Wideti\DomainBundle\Dto\OneGuestQueryDto;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\DeviceAccess;
use Wideti\DomainBundle\Entity\DeviceEntry;
use Wideti\DomainBundle\Entity\Guests;
use Wideti\DomainBundle\Exception\Api\EntityNotFountException;
use Wideti\DomainBundle\Exception\MongoDuplicateKeyRegisterException;
use Wideti\DomainBundle\Exception\UniqueFieldException;
use Wideti\DomainBundle\Helpers\DateTimeHelper;
use Wideti\DomainBundle\Helpers\NasHelper;
use Wideti\DomainBundle\Helpers\EncryptDecryptHelper;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\AuditException;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\EventCreate;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\EventUpdate;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\KindGuest;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\KindUserAdmin;
use Wideti\DomainBundle\Service\AuditLogInternal\AuditLogService;
use Wideti\DomainBundle\Service\BouncedValidation\BouncedValidationImp;
use Wideti\DomainBundle\Service\Guest\Dto\GuestAccessReportFilter;
use Wideti\DomainBundle\Service\GuestDevices\GuestDevices;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\GuestToAccountingProcessor;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\SendGuestToAccountingProcessorImp;
use Wideti\DomainBundle\Service\RadacctReport\Dto\GuestAccessReport;
use Wideti\DomainBundle\Service\SignUp\Exceptions\GuestAlreadyExistsException;
use Wideti\DomainBundle\Service\Timezone\TimezoneService;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\DomainBundle\Service\Group\GroupServiceAware;
use Wideti\DomainBundle\Service\GuestNotification\Base\NotificationType;
use Wideti\DomainBundle\Service\GuestSocial\GuestSocialServiceAware;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\DomainBundle\Service\NasManager\Dto\RadiusPolicy\TimeLimitPolicy;
use Wideti\DomainBundle\Service\NasManager\NasServiceAware;
use Wideti\DomainBundle\Service\RadacctReport\RadacctReportService;
use Wideti\DomainBundle\Service\Radcheck\RadcheckAware;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;
use Wideti\DomainBundle\Validator\CpfValidate;
use Wideti\DomainBundle\Validator\DomainEmailValidate;
use Wideti\DomainBundle\Validator\EmailValidate;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\EventDispatcherAware;
use Wideti\WebFrameworkBundle\Aware\FormAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\RouterAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\DomainBundle\Helpers\PasswordGenerator;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\RadacctRepositoryAware;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearchAware;
use Wideti\DomainBundle\Service\Hubsoft\HubsoftService;
use Wideti\DomainBundle\Service\Ixc\IxcService;

class GuestService
{
    const RECURRENT_TYPE_UNIQUE     = 'unique';
    const RECURRENT_TYPE_RETURNING  = 'returning';

    use EntityManagerAware;
    use MongoAware;
    use TwigAware;
    use RadcheckAware;
    use EventDispatcherAware;
    use TranslatorAware;
    use GuestSocialServiceAware;
    use LoggerAware;
    use ModuleAware;
    use RouterAware;
    use GroupServiceAware;
    use FormAware;
    use CustomFieldsAware;
    use SessionAware;
    use NasServiceAware;
    use RadacctRepositoryAware;
    use ElasticSearchAware;

    /**
     * @var ContainerInterface
     */
    private $container;
    private $bounceValidatorActive;

    /**
     * @var Client
     */
    protected $client;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
    * @var RadacctReportService
    */
    private $radacctReportService;
    /**
     * @var CacheServiceImp
     */
    private $cacheService;
    /**
     * @var TimezoneService
     */
    private $timezoneService;
    /**
     * @var GuestToAccountingProcessor
     */
    private $accountingProcessor;
    /**
     * @var GuestDevices
     */
    private $guestDevices;
    /**
     * @var GuestRepository
     */
    private $guestRepository;
    /**
     * @var BouncedValidationImp
     */
    private $bouncedValidation;

    /**
     * @var HubsoftService
     */
    private $hubsoftService;
    
        /**
     * @var IxcService
     */
    private $IxcService;

    /**
     * @var auditLogService
     */
    private $auditLogService;

    /**
     * GuestService constructor.
     * @param GuestRepository $guestRepository
     * @param RadacctReportService $radacctReportService
     * @param ConfigurationService $configurationService
     * @param ContainerInterface $container
     * @param CacheServiceImp $cacheService
     * @param TimezoneService $timezoneService
     * @param GuestToAccountingProcessor $accountingProcessor
     * @param GuestDevices $guestDevices
     * @param BouncedValidationImp $bouncedValidation
     * @param $bounceValidatorActive
     * @param HubsoftService $hubsoftService
     * @param IxcService $IxcService
     *
     */
    public function __construct(
        GuestRepository $guestRepository,
        RadacctReportService $radacctReportService,
        ConfigurationService $configurationService,
        ContainerInterface $container,
        CacheServiceImp $cacheService,
        TimezoneService $timezoneService,
        GuestToAccountingProcessor $accountingProcessor,
        GuestDevices $guestDevices,
        BouncedValidationImp $bouncedValidation,
        $bounceValidatorActive,
        HubsoftService $hubsoftService,
        IxcService $IxcService,
        AuditLogService $auditLogService

    ) {
        $this->guestRepository          = $guestRepository;
        $this->radacctReportService     = $radacctReportService;
        $this->configurationService     = $configurationService;
        $this->container                = $container;
        $this->cacheService             = $cacheService;
        $this->timezoneService          = $timezoneService;
        $this->accountingProcessor      = $accountingProcessor;
        $this->guestDevices             = $guestDevices;
        $this->bouncedValidation        = $bouncedValidation;
        $this->bounceValidatorActive    = $bounceValidatorActive;
        $this->hubsoftService           = $hubsoftService;
        $this->IxcService               = $IxcService;
        $this->auditLogService          = $auditLogService;
    }

    /**
     * @param Guest $guest
     * @param string $locale
     * @param Client|null $client
     * @return Guest
     * @throws MongoDuplicateKeyRegisterException
     * @throws AuditException
     */
    public function createByApi(Guest $guest, $locale = "pt_br", Client $client = null)
    {
        if (array_key_exists('email', $guest->getProperties())) {
            $emailValidation = true;

            if ($this->bounceValidatorActive) {
                try {
                    $emailValidation = $this->bouncedValidation->isValid($guest->getProperties()['email']);
                } catch (\Exception $e) {
                    $this->logger->addCritical('Fail to validate e-mail on ServiceBounce API', [
                        'errorMessage' => $e->getMessage(),
                        'error' => $e
                    ]);
                }
            }

            $guest->setEmailIsValid(boolval($emailValidation));
            $guest->setEmailIsValidDate(new \MongoDate());
        }

        if (!$guest->getRegistrationMacAddress()) {
            $guest->setRegistrationMacAddress(Guest::REGISTER_BY_API);
        }

        $guest->setRegisterMode(Guest::REGISTER_BY_API);
        $guest->setDocumentType($guest->documentTypeFromLocale($locale));
        $guest->setLocale($locale);

        if (!$guest->getGroup()) {
            $guest->setGroup(Group::GROUP_DEFAULT);
        }

        $this->client = $client;

        if ($guest->getStatus() === null) {
            $guest->setStatus(Guest::STATUS_ACTIVE);
        }

        $configuration = $this->configurationService->getByIdentifierOrDefault(
            $guest->getRegistrationMacAddress(),
            $client
        );

        $guest = $this->parseStringBornDateToMongoDate($guest);
        $guest->setLoginField($this->customFieldsService->getLoginFieldIdentifier());
        $guest->setTimezone($this->timezoneService->getAccessPointTimezone($guest->getRegistrationMacAddress()));
        $guest->setHasConsentRevoke(false);

        $guest = $this->persist($guest);

        if ($configuration['confirmation_email']) {
            $this->radcheckService->setExpirationTime(
                $this->client,
                $guest,
                $configuration['confirmation_email_limit_time'],
                TimeLimitPolicy::EMAIL_CONFIRMATION
            );
        } else {
            $defaultGroup = $this->mongo
                ->getRepository('DomainBundle:Group\Group')
                ->findOneByShortcode(Group::GROUP_DEFAULT);

            if ($this->groupService->moduleIsActive($defaultGroup, Configuration::BLOCK_PER_TIME)) {
                $this->radcheckService->setExpirationTime(
                    $this->client,
                    $guest,
                    $this->groupService->getConfigurationValue(
                        $defaultGroup,
                        Configuration::BLOCK_PER_TIME,
                        'block_per_time_time'
                    ),
                    TimeLimitPolicy::BLOCK_PER_TIME
                );
            }
        }

        $this->accountingProcessor->process($client, $guest);

        return $guest;
    }

    public function updateByApi(Guest $guest, $locale = "pt_br", Client $client = null)
    {
        $guest->setDocumentType($guest->documentTypeFromLocale($locale));
        $guest->setLocale($locale);

        $this->client = $client;

        $guest = $this->persist($guest);

        $fullEntity = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneBy(['mysql' => $guest->getMysql()]);

        $this->accountingProcessor->process($client, $fullEntity);

        return $guest;
    }

    public function updateByApi_v2(Guest $guest, $locale = "pt_br", Client $client = null)
    {
        $guest->setDocumentType($guest->documentTypeFromLocale($locale));
        $guest->setLocale($locale);

        $this->client = $client;
        $fullEntity = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneBy(['mysql' => $guest->getMysql()]);
        $guest->setCreated($fullEntity->getCreated());

        $guest = $this->persist($guest);

        $fullEntity = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneBy(['mysql' => $guest->getMysql()]);

        $this->accountingProcessor->process($client, $fullEntity);

        $result = $this->convertGuestMongoDateToDate($guest);
        $result = $this->convertDateTimeToUnixTimestamp($result);

        return [
            "apiResponse" => $result->jsonSerialize_v2(),
            "updatedEntity" => $guest
        ];
    }

    /**
     * @param Guest $guest
     * @param bool $emailValidate
     * @param bool $sendEmail
     * @return Guest
     * @throws UniqueFieldException
     */
    public function createByAdmin(Guest $guest, $emailValidate = true, $sendEmail = true)
    {
        $client = $this->session->get('wspotClient');
        $nas    = $this->session->get(Nas::NAS_SESSION_KEY);

        $guest->setDocumentType($guest->documentTypeFromLocale($guest->getLocale()));
        $this->serverSideValidation($guest, $guest->getLocale());

        $fieldExists = $this->isFieldExists($guest);

        if ($fieldExists) {
            throw new UniqueFieldException($fieldExists);
        }

        $this->client = $this->em
            ->getRepository('DomainBundle:Client')
            ->find($client)
        ;

        if (array_key_exists('name', $guest->getProperties())) {
            $guest->addProperty('name', ucfirst($guest->getProperties()['name']));
        }

        if (array_key_exists('email', $guest->getProperties())) {
            $guest->addProperty('email', strtolower($guest->getProperties()['email']));

            $guest->setEmailIsValid(boolval($emailValidate));
            $guest->setEmailIsValidDate(new MongoDate());
        }
        $this->formatMultipleChoiceFields($guest);

        $registrationMacAddress = ($guest->getRegistrationMacAddress())
            ? $guest->getRegistrationMacAddress()->getIdentifier() : '';
        $guest->setRegistrationMacAddress($registrationMacAddress);

        if (!$guest->getRegisterMode()) {
            $guest->setRegisterMode(Guest::REGISTER_BY_ADMIN);
        }

        $this->savePassword($guest);
        $guest->setTimezone($this->timezoneService->getAccessPointTimezone($guest->getRegistrationMacAddress()));
        $guest->setHasConsentRevoke(false);
        $guest->setLoginField($this->customFieldsService->getLoginFieldIdentifier());

        $guest = $this->persist($guest);

        if ($this->moduleService->checkModuleIsActive('router_box') === false &&
            $this->moduleService->checkModuleIsActive('access_code') === false) {
            $defaultGroup = $this->mongo
                ->getRepository('DomainBundle:Group\Group')
                ->findOneByShortcode(Group::GROUP_DEFAULT);

            if ($this->groupService->moduleIsActive($defaultGroup, Configuration::BLOCK_PER_TIME)) {
                $this->radcheckService->setExpirationTime(
                    $this->client,
                    $guest,
                    $this->groupService->getConfigurationValue(
                        $defaultGroup,
                        Configuration::BLOCK_PER_TIME,
                        'block_per_time_time'
                    ),
                    TimeLimitPolicy::BLOCK_PER_TIME
                );
            }
        }

        if ($sendEmail) {
            $params = [
                'domain'        => $this->getLoggedClient()->getDomain(),
                'guestId'       => $guest->getId(),
                'locale'        => 'pt_br',
                'loginField'    => $this->customFieldsService->getLoginField()
            ];

            $register = $this->container->get(NotificationType::REGISTER);
            $register->send($nas, $params);
        }

        $this->accountingProcessor->process($client, $guest);


        return $guest;
    }

    /**
     * @param Nas|null $nas
     * @param Guest|null $guest
     * @param string $locale
     * @param bool $emailValidate
     * @param null $registerMode
     * @return Guest
     * @throws MongoDuplicateKeyRegisterException
     * @throws UniqueFieldException
     * @throws \Exception
     */
    public function createByFrontend(
        Nas $nas = null,
        Guest $guest = null,
        $locale = 'pt_br',
        $emailValidate = true,
        $registerMode = null
    ) {
        if ($guest == null) {
            throw new \InvalidArgumentException("Guest can't be null");
        }

        $client = $this->getLoggedClient();

        $this->client = $this->em
            ->getRepository('DomainBundle:Client')
            ->find($this->session->get('wspotClient'))
        ;

        $this->serverSideValidation($guest, $locale);

        $fieldExists = $this->isFieldExists($guest);

        if ($fieldExists) {
            if ($client->getDomain() == 'mercantil') {
                $properties = $guest->getProperties();
                $guest = $this->mongo
                    ->getRepository('DomainBundle:Guest\Guest')
                    ->findOneBy([
                        'properties.' . $fieldExists => new \MongoRegex('/^'.$properties[$fieldExists].'$/i')
                ]);
                $params = [
                    'domain'        => $this->getLoggedClient()->getDomain(),
                    'guestId'       => $guest->getId(),
                    'locale'        => $locale,
                    'macAddress'    => $nas->getAccessPointMacAddress()
                ];
                $notification = $this->container->get(NotificationType::CONFIRMATION);
                $notification->send($nas, $params);
                $notification->sendSMS($nas, $params);
            }

            throw new UniqueFieldException($fieldExists);
        }

        if ($locale == null) {
            $locale = 'pt_br';
        }

        $guestData = $this->session->get('guest');
        $guestInfo = null;

        if ($guestData) {
            $guestInfo = $guestData['data'];
        }
        if ($nas !== null) {
            $guest->setRegistrationMacAddress($nas->getAccessPointMacAddress());
        }
        
        if ($registerMode != Social::HUBSOFT) {
            try {
                $resp = $this->hubsoftService->prospectAction($guest);
            } catch (\Exception $e) {
                $resp = null;
            }
        }


        
        
        $guestName = (array_key_exists('name', $guest->getProperties())) ? $guest->getProperties()['name'] : null;
        $guestName = isset($guestInfo['name']) ? $guestInfo['name'] : $guestName;
        
        if (!$this->configurationService->get($nas, $client, 'confirmation_sms')
            && !$this->configurationService->get($nas, $client, 'confirmation_email')) {
            $guest->setStatus($guest::STATUS_ACTIVE);
        } else {
            $guest->setStatus($guest::STATUS_PENDING_APPROVAL);
        }

        $guest->setLocale($locale);
        $guest->setDocumentType($guest->documentTypeFromLocale($locale));


        if (array_key_exists('name', $guest->getProperties())) {
            $guest->addProperty('name', ucfirst($guestName));
        }

        if (array_key_exists('email', $guest->getProperties())) {
            $guest->addProperty('email', strtolower($guest->getProperties()['email']));
            $guest->setEmailIsValid(boolval($emailValidate));
            $guest->setEmailIsValidDate(new \MongoDate());
        }

        if (is_null($guest->getGroup())) {
            $guest->setGroup($this->groupService->getGuestGroupFromNas($nas));
        }

        $guest->setRegisterMode($this->getRegisterMode($registerMode));
        $this->savePassword($guest);
        $guest->setTimezone($this->timezoneService->getAccessPointTimezone($guest->getRegistrationMacAddress()));
        $guest->setHasConsentRevoke(false);
        
        $guest = $this->persist($guest);
        
        if ($registerMode != Social::IXC) {
            try {
                $resp = $this->IxcService->prospectAction($guest);
            } catch (\Exception $e) {
                $resp = null;
                $this->logger->error($e->getMessage());
            }
        }
        if ($this->moduleService->checkModuleIsActive('access_code') === false) {
            if ($this->configurationService->get($nas, $client, 'confirmation_email')) {
                $this->radcheckService->setExpirationTime(
                    $this->client,
                    $guest,
                    $this->configurationService->get($nas, $client, 'confirmation_email_limit_time'),
                    TimeLimitPolicy::EMAIL_CONFIRMATION
                );
            }
        }

        $this->session->set('edit', $guest->getMysql());

        $params = [
            'domain'        => $this->getLoggedClient()->getDomain(),
            'guestId'       => $guest->getId(),
            'locale'        => $locale,
            'macAddress'    => $nas->getAccessPointMacAddress()
        ];

        $notification = $this->container->get(NotificationType::CONFIRMATION);
        $notification->send($nas, $params);
        $notification->sendSMS($nas, $params);

        return $guest;
    }

    private function serverSideValidation(Guest $guest, $locale = 'pt_br')
    {
        $documentField  = $this->customFieldsService->getFieldByNameType('document');
        $emailField     = $this->customFieldsService->getFieldByNameType('email');

        if ($locale == 'pt_br') {
            if (array_key_exists('document', $guest->getProperties()) && $this->customFieldsService->isRequired($documentField)) {
                $document = $guest->getProperties()['document'];
                if (is_numeric($document) === false || strlen($document) < 11) {
                    throw new \Exception('invalid_document');
                }

                $validation = new CpfValidate();
                if (!$validation->validate($document)) {
                    throw new \Exception('invalid_document');
                }
            }

            if (array_key_exists('phone', $guest->getProperties())) {
                if ((is_numeric($guest->getProperties()['phone']) === false || strlen($guest->getProperties()['phone']) < 10)
                    && (array_key_exists('dialCodePhone', $guest->getProperties())
                        &&  ($guest->getProperties()['dialCodePhone'] == "55"))) {
                    throw new \Exception('invalid_phone');
                }
            }

            if (array_key_exists('mobile', $guest->getProperties())) {
                if ((is_numeric($guest->getProperties()['mobile']) === false || strlen($guest->getProperties()['mobile']) < 10)
                    && (array_key_exists('dialCodePhone', $guest->getProperties())
                        &&  ($guest->getProperties()['dialCodePhone'] == "55"))) {
                    throw new \Exception('invalid_phone');
                }
            }
        }

        if ($emailField && array_key_exists('email', $guest->getProperties()) &&
            $this->customFieldsService->isRequired($emailField)
        ) {
            $email = $guest->getProperties()['email'];

            $mxValidation = new EmailValidator();
            if (!$mxValidation->isValid($email, true)) {
                throw new \Exception('invalid_email');
            }

            $validation = new EmailValidate();
            if ($validation->validate($email)) {
                throw new \Exception('invalid_email');
            }

            $domainValidation = new DomainEmailValidate();
            if (!$domainValidation->validate($email)) {
                throw new \Exception('invalid_domain');
            }

            $emailValidateRegex = '/(?:[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/';
            if (!preg_match($emailValidateRegex, $email)) {
                throw new \Exception('invalid_domain');
            }
        }

        if (array_key_exists('data_nascimento', $guest->getProperties())) {
            $birthdate = $guest->getProperties()['data_nascimento'];
            $birhdateValidate = new DateTimeHelper();
            if (!$birhdateValidate->validateBirthdate($birthdate)) {
                throw new \Exception('invalid_birthdate');
            }
        }
        if (array_key_exists('age_restriction', $guest->getProperties())) {
            $birthdate = $guest->getProperties()['age_restriction'];
            $birhdateValidate = new DateTimeHelper();
            if (!$birhdateValidate->validateAgeRestriction($birthdate)) {
                throw new \Exception('age_restriction');
            }
        }
    }

    public function savePassword(Guest $guest)
    {
        $password = $guest->getPassword();

        if (!$password) {
            $passwordGenerator  = new PasswordGenerator();
            $password           = $passwordGenerator->generate($guest);
        }

        $guest->setPassword($password);
    }

    public function confirm(Guest $guest = null, Nas $nas = null)
    {
        $client = $this->getLoggedClient();

        if ($guest == null) {
            throw new \InvalidArgumentException("On confirmation, guest can't be null");
        }

        // Workaround to be removed ASAP
        // This recreate the guest document to trigger webhook with SMS/Email confirmation
        // This is necessary because the webhook is triggered by fluend mongo_tail plugin
        // only in guest insert operations and the confirmation is done by a update operation
        if ($client->getDomain() == 'mercantil') {
            $this->mongo->remove($guest);
            $this->mongo->flush();
            $guest->setId(null);
        }

        $guest->setStatus($guest::STATUS_ACTIVE);
        $guest->setValidated(new DateTime());

        $this->persist($guest);

        if ($this->configurationService->get($nas, $client, 'confirmation_email')) {
            $this->radcheckService->removeExpirationTimeByGuest($this->getLoggedClient(), $guest);
        }

        if ($this->hasFieldToSendConfirmation($guest)) {
            $params = [
                'domain'        => $this->getLoggedClient()->getDomain(),
                'guestId'       => $guest->getId(),
                'locale'        => $guest->getLocale(),
                'macAddress'    => $guest->getRegistrationMacAddress(),
                'loginField'    => $this->customFieldsService->getLoginField()
            ];

            $notification = $this->container->get(NotificationType::REGISTER);
            $notification->send($nas, $params);
        }
    }

    /**
     * @param Guest $guest
     * @return bool
     */
    private function hasFieldToSendConfirmation(Guest $guest)
    {
        $fieldsToSendConfirmation = [
            'email', 'phone', 'mobile'
        ];

        foreach (array_keys($guest->getProperties()) as $field) {
            if (in_array($field, $fieldsToSendConfirmation)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $guest
     * @return bool
     */
    public function hasEmailFieldInProperties($guest)
    {
        return array_key_exists('email', $guest->getProperties());
    }

    public function update(Guest $guest)
    {
        $fieldExists = $this->isFieldExists($guest);

        if ($fieldExists) {
            throw new UniqueFieldException($fieldExists);
        }

        if ($guest->getRegistrationMacAddress() instanceof AccessPoints) {
            $guest->setRegistrationMacAddress($guest->getRegistrationMacAddress()->getIdentifier());
        }

        $guest = $this->lowerCaseEmail($guest);
        $this->formatMultipleChoiceFields($guest);
        $this->persist($guest);

        $this->accountingProcessor->process($this->getLoggedClient(), $guest);
    }

    public function formatMultipleChoiceToArray($guest) {
        $fields = $this->customFieldsService->getCustomFields();
        $multipleChoiceFields = [];
        foreach ($fields as $field) {
            if ($field->getType() == "multiple_choice") {
                $multipleChoiceFields[] = $field->getIdentifier();
            }
        }
        $properties = $guest->getProperties();
        foreach ($multipleChoiceFields as $multipleChoiceField) {
            if (isset($properties[$multipleChoiceField]) && !$properties[$multipleChoiceField]) {
                $properties[$multipleChoiceField] = [];
            }
            if (isset($properties[$multipleChoiceField]) && $properties[$multipleChoiceField] && is_string($properties[$multipleChoiceField])) {
                $properties[$multipleChoiceField] = explode(' - ', $properties[$multipleChoiceField]);
            }
        }
        $guest->setProperties($properties);
    }

    private function formatMultipleChoiceFields($guest) {
        $fields = $this->customFieldsService->getCustomFields();
        $multipleChoiceFields = [];
        foreach ($fields as $field) {
            if ($field->getType() == "multiple_choice") {
                $multipleChoiceFields[] = $field->getIdentifier();
            }
        }
        if (!$multipleChoiceFields) {
          return ;
        }
        $properties = $guest->getProperties();
        foreach ($multipleChoiceFields as $multipleChoiceField) {
            $properties[$multipleChoiceField] = implode(' - ', $properties[$multipleChoiceField]);
        }
        $guest->setProperties($properties);
    }

    public function delete(Guest $guest)
    {
        $entity = $this->em
            ->getRepository("DomainBundle:Guests")
            ->find($guest->getMysql())
        ;

        $this->em
            ->getRepository("DomainBundle:GuestAuthCode")
            ->deleteByGuest($entity->getId())
        ;

        try {
            $this->em->remove($entity);
            $this->em->flush();

            $this->mongo->remove($guest);
            $this->mongo->flush();
        } catch (\Exception $e) {
            $this->logger->addCritical('Fail to delete guest - ' . $e->getMessage());
        }
    }

    public function deleteAllByClient($client)
    {
        $connection = $this->mongo->getConnection()->getMongoClient();
        $database   = $connection->selectDB($client);
        $collection = $database->guests;
        $collection->remove();
    }

    public function getPassword(Guest $guest)
    {
        $radcheck = $this->em
            ->getRepository("DomainBundle:Radcheck")
            ->findOneBy(
                [
                    'guest'     => $guest->getMysql(),
                    'attribute' => 'Cleartext-Password',
                    'client'    => $this->getLoggedClient()
                ]
            )
        ;
        if (!$radcheck) {
            throw new NotFoundException('Guest/Password not found.');
        }
        return $radcheck->getValue();
    }

    public function changePassword(Nas $nas = null, Guest $guest = null, $password, $changeByAdmin = false)
    {
        if ($guest == null) {
            throw new \InvalidArgumentException("Cant change password with guest null.");
        }
        if (!$password && !$guest->getResetPassword()) {
            $passwordGenerator  = new PasswordGenerator();
            $password           = $passwordGenerator->generate();
            $guest->setPassword($password);
            $guest->setResetPassword(true);
            $this->persist($guest);
        } else if ($password) {
            $guest->setPassword($password);
            $guest->setResetPassword(false);
            $this->persist($guest);
        } else {
            $password = $guest->getPassword();
        }
        
        
        $guestEmail = $guest->getPropertyByKey(Guest::PROPERTY_EMAIL);

        if ($guestEmail) {
            $notification = $this->container->get(NotificationType::PASSWORD);
            $params = [
                'domain'                => $this->getLoggedClient()->getDomain(),
                'guestId'               => $guest->getId(),
                'locale'                => $guest->getLocale(),
                'changePasswordByAdmin' => $changeByAdmin,
                'password'              => EncryptDecryptHelper::encrypt($password, $guestEmail)
            ];

            $notification->send($nas, $params);
        }
        $this->em
            ->getRepository("DomainBundle:DeviceEntry")
            ->createQueryBuilder('de')
            ->update()
            ->set('de.hasChangePassword', 'true')
            ->where('de.guest = :guest')
            ->setParameter('guest', $guest->getMysql())
            ->getQuery()
            ->execute();

    }
    public function sendPasswordSms(Guest $guest, Nas $nas = null)
    {
        $client = $this->getLoggedClient();

        if ($this->configurationService->get($nas, $client, 'enable_welcome_sms') == 1) {
            $register = $this->container->get('wspot.notification.sms_password');
            $register->sendSMS($nas, [
                'guestId' => $guest->getId()
            ]);
        }
    }

    /**
     * @param Guest $guest
     * @return bool
     * @throws \ReflectionException
     * @throws \Wideti\DomainBundle\Exception\NasEmptyException
     * @throws \Wideti\DomainBundle\Exception\NasWrongParametersException
     */
    public function resendConfirmationUrl(Guest $guest)
    {
        $client = $this->getLoggedClient();

        $accessPoint = $this->em
            ->getRepository('DomainBundle:AccessPoints')
            ->getAccessPointByIdentifier($guest->getRegistrationMacAddress(), $client)
        ;

        if (empty($accessPoint) || $accessPoint[0]->getVendor() === null || !$guest->getNasRaw()) {
            return false;
        }

        $nas = $this->session->get(Nas::NAS_SESSION_KEY);
        if (!$nas) {
            $nas = $this->nasService->createNasManually($accessPoint[0]->getVendor(), $guest);
            $this->session->set(Nas::NAS_SESSION_KEY, $nas);
        }

        if ($this->configurationService->get($nas, $client, 'confirmation_email') == 1) {
            $params = [
                'domain'        => $this->getLoggedClient()->getDomain(),
                'guestId'       => $guest->getId(),
                'locale'        => $guest->getLocale(),
                'macAddress'    => (isset($nas)) ? $nas->getAccessPointMacAddress() : ''
            ];

            $notification = $this->container->get(NotificationType::RESEND_CONFIRMATION);
            $notification->send($nas, $params);
        }

        return true;
    }

    public function verifyUser($data = null)
    {
        if (!$data) {
            return false;
        }

        $loginField = $this->customFieldsService->getLoginFieldIdentifier();

        $guest = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneBy([
                'properties.' . $loginField => $data->get($loginField)
            ])
        ;

        if ($guest) {
            return $guest;
        }

        return false;
    }

    public function returningGuest(Guest $guest)
    {
        $this->client = $this->em
            ->getRepository('DomainBundle:Client')
            ->find($this->session->get('wspotClient'))
        ;

        $lastAccess = $guest->getLastAccess();
        $this->session->set('lastAccess', $lastAccess);
        $today = new \DateTime('NOW');
        if (!is_null($lastAccess)) {
            $lastAccess->setTime(0, 0, 0); // Zera as horas para considerar apenas o dia
            $today->setTime(0, 0, 0);
        }

        if (is_null($lastAccess)){
            $guest->setReturning(false);
        }elseif ($lastAccess->diff($today)->days > 0){
            $guest->addVisit();
            $guest->setReturning(true);
            $guest->setLastAccess(new \MongoDate());
        }

        $this->persist($guest);
    }

    public function checkCredentials($properties, $password, $nas= null)
    {
        $loginField = $this->customFieldsService->getLoginFieldIdentifier();
        $guest      = $this->mongo
                ->getRepository('DomainBundle:Guest\Guest')
                ->findOneBy([
                    'properties.' . $loginField => $properties[$loginField]
                ])
            ;

        if ($guest === null) {
            throw new AuthenticationCredentialsNotFoundException(
                $this->translator->trans('wspot.login_page.login_wrong_data')
            );
        }

        if ($guest->getStatus() == Guest::STATUS_INACTIVE) {
            throw new DisabledException($this->translator->trans('wspot.login_page.login_inactive_user'));
        }

        if ($password != $guest->getPassword()) {
            throw new AuthenticationCredentialsNotFoundException(
                $this->translator->trans('wspot.login_page.login_wrong_data')
            );
        }

        if($nas){
            $guestMacAddress = $nas->getGuestDeviceMacAddress();
            $this->em
                ->getRepository("DomainBundle:DeviceEntry")
                ->createQueryBuilder('de')
                ->update()
                ->set('de.hasChangePassword', 'false')
                ->where('de.guest = :guest')
                ->andWhere('de.device = :device')
                ->setParameter('guest', $guest->getMysql())
                ->setParameter('device', $guestMacAddress)
                ->getQuery()
                ->execute();
                }
    }

    public function verifyUserBlockPerTime(Guest $guest)
    {
        $nas        = $this->session->get(Nas::NAS_SESSION_KEY);
        $client     = $this->getLoggedClient();
        $expiration = $this->em
            ->getRepository('DomainBundle:Radcheck')
            ->findOneBy([
                'client'    => $this->getLoggedClient(),
                'guest'     => $guest->getMysql(),
                'attribute' => 'Expiration'
            ]);

        if ($expiration) {
            if ($guest->getStatus() != Guest::STATUS_BLOCKED) {
                $limitTime = date_create($expiration->getValue());
                $limitTime = $limitTime->format('Y-m-d H:i:s');
                $dateNow   = date('Y-m-d H:i:s');

                if ($limitTime < $dateNow) {
                    $this->updateStatus($guest, $guest::STATUS_BLOCKED);
                    return true;
                }
            }

            if ($guest->getStatus() == Guest::STATUS_BLOCKED) {
                $blockTime = strtoupper(
                    $this->configurationService->get($nas, $client, 'confirmation_email_block_time')
                );
                $blockTime = str_replace(['D', 'H', 'M', 'S'], [' DAY', ' HOUR', ' MINUTE', ' SECOND'], $blockTime);

                $limitTime = date_create($expiration->getValue());
                $limitTime = $limitTime->format('Y-m-d H:i:s');

                $blockTime = date('Y-m-d H:i:s', strtotime('+'.$blockTime, strtotime($limitTime)));

                $dateNow   = date('Y-m-d H:i:s');

                if ($dateNow > $blockTime) {
                    $this->radcheckService->removeExpirationTimeByGuest($this->getLoggedClient(), $guest);

                    $this->radcheckService->setExpirationTime(
                        $client,
                        $guest,
                        $this->configurationService->get($nas, $client, 'confirmation_email_limit_time'),
                        TimeLimitPolicy::EMAIL_CONFIRMATION
                    );

                    $this->updateStatus($guest, $guest::STATUS_PENDING_APPROVAL);

                    return false;
                }

                return true;
            }
        }

        return false;
    }

    public function updateLastAccess(Guest $guest)
    {
        $this->client = $this->em
            ->getRepository('DomainBundle:Client')
            ->find($this->session->get('wspotClient'))
        ;

        $guest->setLastAccess(new \MongoDate());

        $this->mongo->persist($guest);
        $this->mongo->flush();

        $this->accountingProcessor->process($this->getLoggedClient(), $guest);
    }

    public function updateLastPolicyIdCreated(Guest $guest, $policyId)
    {
        $guest->setLastPolicyIdCreated($policyId);
        $this->mongo->persist($guest);
        $this->mongo->flush();
    }

    public function updateStatus(Guest $guest, $status)
    {
        $guest->setStatus($status);
        $this->persist($guest);
    }

    public function validate($field, $value)
    {
        if ($field == 'email') {
            $domainEmailValidate = new DomainEmailValidate();

            if ($domainEmailValidate->validate($value)) {
                return true;
            }
        }
        return false;
    }

    public function persist(Guest $guest)
    {
        $new = false;
        $nas = $this->session->get(Nas::NAS_SESSION_KEY);

        if ($nas) {
            $guest->setNasVendor($nas->getVendorName());
            $guest->setNasRaw(NasHelper::encodeRawParametersToUrl($nas->getVendorRawParameters()));
        }

        if ($guest->getMysql() == null) {
            $new = true;
            $mysqlGuest = new Guests();
            $mysqlGuest->setClient($this->client);

            $this->em->persist($mysqlGuest);
            $this->em->flush();

            try {
                $searchedGuest = $this->em->getRepository("DomainBundle:Guests")
                        ->findOneBy(['id' => $mysqlGuest->getId()]);

                if (!$searchedGuest) {
                    throw new \Exception("Fail to get guest after register ". $mysqlGuest);
                }
            } catch (\Exception $e) {
                $this->logger->addCritical("Fail to find mysql guest after create ". $mysqlGuest);
            }

            $guest->setMysql($mysqlGuest->getId());
        }

        try {
            $this->mongo->persist($guest);
            $this->mongo->flush();
            $this->mongo->refresh($guest);
        } catch (\MongoDuplicateKeyException $e) {
            throw new MongoDuplicateKeyRegisterException($e);
        } catch (\Exception $e) {
            if ($new) {
                $this->em->remove($mysqlGuest);
                $this->em->flush();
            }
            throw new \Exception($e);
        }
        return $guest;
    }

    public function findPendingApprovalByMacAddress($guestMacAddress)
    {
        if (empty($guestMacAddress)) {
            return null;
        }

        $client = $this->getLoggedClient();

        $guestIds = $this->guestDevices->getGuestsByMacDevice($client, $guestMacAddress);

        foreach ($guestIds as $guestId) {
            $pendingApprovalGuest = $this->guestRepository->findOneBy([
                'mysql' => $guestId,
                "status" => Guest::STATUS_PENDING_APPROVAL
            ]);

            if ($pendingApprovalGuest) {
                return $pendingApprovalGuest;
            }
        }

        return null;
    }

    /**
     * @param array $filters
     * @param int $limit
     * @param int $page
     * @param string $sort
     * @return RestPaginateDto
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     * @throws \MongoException
     */
    public function getAllGuestsPaginated(array $filters = [], $limit = 10, $page = 1, $sort = "desc")
    {
        $systemLimitPerPage = $this->container->getParameter('api_guests_list_limit_per_page');

        $page   = $page <= 0 ? 1 : $page;
        $sort   = !in_array($sort, ['desc','asc']) ? "desc" : $sort;
        $limit  = $limit <= 0 ? 10 : $limit;
        $offset = ($page - 1) * $limit;

        $optionsUrl = [
            'page'  => $page,
            'limit' => $limit,
            'sort'  => $sort
        ];

        if ($limit > $systemLimitPerPage) {
            $limit = $systemLimitPerPage;
        }

        $qb = $this->mongo->createQueryBuilder('Wideti\DomainBundle\Document\Guest\Guest');
        $qb->skip($offset)->limit($limit)->sort(['created' => $sort]);

        $paginateDto = new RestPaginateDto();

        if (isset($filters['filter']) && $filters['filter'] !== null && $filters['value'] !== null) {
            if ($filters["filter"] == "returning") {
                $filters["value"] == "true" ? $filters["value"] = true : $filters["value"] = false;
                $qb->field($filters['filter'])
                    ->equals($filters["value"]);
            } else {
                $qb->field($filters['filter'])
                    ->equals(new \MongoRegex("/.*" . $filters['value']
                        . ".*/i"));
            }
            $optionsUrl['filter'] = $filters['filter'];
            $optionsUrl['value'] = $filters['value'];
        }

        if (isset($filters['refId'])) {
            $qb->field("mysql")->equals($filters["refId"]);
            $optionsUrl['refId'] = $filters['refId'];
        }

        if (isset($filters['status'])) {
            $qb->field("status")->equals($filters["status"]);
            $optionsUrl['status'] = $filters['status'];
        }

        if (isset($filters['filter']) && isset($filters['from']) && isset($filters['to'])) {
            $formatFrom = (strpos($filters['from'], ':') !== false) ? 'Y-m-d H:i:s' : 'Y-m-d 00:00:00';
            $formatTo   = (strpos($filters['to'], ':') !== false) ? 'Y-m-d H:i:s' : 'Y-m-d 23:59:59';

            try {
                $dateFrom   = new \MongoDate(strtotime(date_format(new DateTime($filters['from']), $formatFrom)));
                $dateTo     = new \MongoDate(strtotime(date_format(new DateTime($filters['to']), $formatTo)));

                $qb->field($filters['filter'])
                    ->gte($dateFrom)
                    ->lte($dateTo);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException("Filtro 'from' ou 'to' inválidos, insira uma data válida.");
            }

            $optionsUrl['filter'] = $filters['filter'];
            $optionsUrl['to'] = $filters['to'];
            $optionsUrl['from'] = $filters['from'];
        }

        $qb->addOr(
            $qb->expr()->addAnd(
                $qb->expr()->field('hasConsentRevoke')->exists(true),
                $qb->expr()->field('hasConsentRevoke')->equals(false)
            )
        );

        $qb->addOr($qb->expr()->field('hasConsentRevoke')->exists(false));

        $result  = $qb->getQuery()->execute();
        $content = [];

        /**
         * @var Guest $row
         */
        foreach ($result as $row) {
            $accessData = [];
            $row        = $this->convertGuestMongoDateToDate($row);
            $guest      = $row->jsonSerialize();

            $guestMySql = $this->em->getRepository('DomainBundle:Guests')
                ->findOneBy(['id' => $row->getMysql()]);

            if ($guestMySql) {
                $devices = $this->guestDevices->getDevices($guestMySql);

                /**
                 * @var DeviceEntry $item
                 */
                foreach ($devices as $item) {
                    array_push($accessData, $item->jsonSerialize_V1());
                }
            }

            $guest['accessData'] = $accessData;
            array_push($content, $guest);
        }

        $totalOfElements = count($result);
        $totalOfPages = ceil($totalOfElements / $limit);

        $paginateDto->setTotalOfElements($totalOfElements);
        $paginateDto->setLimitPerPage($limit);
        $paginateDto->setPage($page);
        $paginateDto->setTotalOfPages($totalOfPages);
        $paginateDto->setElements($content);
        $paginateDto->setOrder($sort);

        if ($page < $totalOfPages && $totalOfElements > 0) {
            $nextOptions         = $optionsUrl;
            $nextOptions['page'] = $nextOptions['page'] + 1;
            $nextLink            = $this->router->generate('api_guests_list', $nextOptions);
            $paginateDto->setNextLink($nextLink);
        }

        if ($page > 1 && $totalOfElements > 0) {
            $previousOptions         = $optionsUrl;
            $previousOptions['page'] = $optionsUrl['page'] - 1;
            $previousLink            = $this->router->generate('api_guests_list', $previousOptions);
            $paginateDto->setPreviusLink($previousLink);
        }

        return $paginateDto;
    }

    /**
     * @param array $filters
     * @param int $limit
     * @param int $page
     * @param string $sort
     * @return RestPaginateDto
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     * @throws \MongoException
     */
    public function getAllGuestsPaginated_v2(array $filters = [], $limit = 10, $page = 1, $sort = "desc")
    {
        $systemLimitPerPage = $this->container->getParameter('api_guests_list_limit_per_page');

        $page   = $page <= 0 ? 1 : $page;
        $sort   = !in_array($sort, ['desc','asc']) ? "desc" : $sort;
        $limit  = $limit <= 0 ? 10 : $limit;
        $offset = ($page - 1) * $limit;

        $optionsUrl = [
            'page'  => $page,
            'limit' => $limit,
            'sort'  => $sort
        ];

        if ($limit > $systemLimitPerPage) {
            $limit = $systemLimitPerPage;
        }

        $qb = $this->mongo->createQueryBuilder('Wideti\DomainBundle\Document\Guest\Guest');
        $qb->skip($offset)->limit($limit)->sort(['created' => $sort]);

        $paginateDto = new RestPaginateDto();

        if (isset($filters['filter']) && $filters['filter'] !== null && $filters['value'] !== null) {
            if ($filters["filter"] == "returning") {
                $filters["value"] == "true" ? $filters["value"] = true : $filters["value"] = false;
                $qb->field($filters['filter'])
                    ->equals($filters["value"]);
            } else {
                $qb->field($filters['filter'])
                    ->equals(new \MongoRegex("/.*" . $filters['value']
                        . ".*/i"));
            }
            $optionsUrl['filter'] = $filters['filter'];
            $optionsUrl['value'] = $filters['value'];
        }

        if (isset($filters['id'])) {
            $qb->field("mysql")->equals($filters["id"]);
            $optionsUrl['id'] = $filters['id'];
        }

        if (isset($filters['status'])) {
            $qb->field("status")->equals($filters["status"]);
            $optionsUrl['status'] = $filters['status'];
        }

        if (isset($filters['filter']) && isset($filters['from']) && isset($filters['to'])) {
            $formatFrom = (strpos($filters['from'], ':') !== false) ? 'Y-m-d H:i:s' : 'Y-m-d 00:00:00';
            $formatTo   = (strpos($filters['to'], ':') !== false) ? 'Y-m-d H:i:s' : 'Y-m-d 23:59:59';

            try {
                $dateFrom   = new \MongoDate(strtotime(date_format(new DateTime($filters['from']), $formatFrom)));
                $dateTo     = new \MongoDate(strtotime(date_format(new DateTime($filters['to']), $formatTo)));

                $qb->field($filters['filter'])
                    ->gte($dateFrom)
                    ->lte($dateTo);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException("Filtro 'from' ou 'to' inválidos, insira uma data válida.");
            }

            $optionsUrl['filter'] = $filters['filter'];
            $optionsUrl['to'] = $filters['to'];
            $optionsUrl['from'] = $filters['from'];
        }

        $qb->addOr(
            $qb->expr()->addAnd(
                $qb->expr()->field('hasConsentRevoke')->exists(true),
                $qb->expr()->field('hasConsentRevoke')->equals(false)
            )
        );

        $qb->addOr($qb->expr()->field('hasConsentRevoke')->exists(false));

        $result  = $qb->getQuery()->execute();
        $content = [];

        /**
         * @var Guest $row
         */
        foreach ($result as $row) {
            $row    = $this->convertGuestMongoDateToDate($row);
            $row    = $this->convertDateTimeToUnixTimestamp($row);
            $guest  = $row->jsonSerialize_v2();
            array_push($content, $guest);
        }

        $totalOfElements = count($result);
        $totalOfPages = ceil($totalOfElements / $limit);

        $paginateDto->setTotalOfElements($totalOfElements);
        $paginateDto->setLimitPerPage($limit);
        $paginateDto->setPage($page);
        $paginateDto->setTotalOfPages($totalOfPages);
        $paginateDto->setElements($content);
        $paginateDto->setOrder($sort);

        if ($page < $totalOfPages && $totalOfElements > 0) {
            $nextOptions         = $optionsUrl;
            $nextOptions['page'] = $nextOptions['page'] + 1;
            $nextLink            = $this->router->generate('api_guests_list', $nextOptions);
            $paginateDto->setNextLink($nextLink);
        }

        if ($page > 1 && $totalOfElements > 0) {
            $previousOptions         = $optionsUrl;
            $previousOptions['page'] = $optionsUrl['page'] - 1;
            $previousLink            = $this->router->generate('api_guests_list', $previousOptions);
            $paginateDto->setPreviusLink($previousLink);
        }

        return $paginateDto;
    }

    public function getGuestDevices($guestId)
    {
        $guest = $this->em->getRepository('DomainBundle:Guests')
            ->findOneBy([
                'id' => $guestId
            ]);

        $validGuest = $this->getGuestByMysql(intval($guestId));

        if (!$guest) {
            throw new EntityNotFountException();
        }

        if (!is_null($validGuest) && $validGuest->isHasConsentRevoke()) {
            throw new Forbidden403Exception();
        }


        $devices = $this->guestDevices->getDevices($guest);

        $content = [];

        /**
         * @var DeviceEntry $device
         */
        foreach ($devices as $device) {
            $device = $device->jsonSerialize();
            unset($device['client_id']);
            unset($device['guest_id']);
            array_push($content, $device);
        }

        return $content;
    }

    /**
     * @param $id
     * @return array|mixed|null
     * @throws \Exception
     */
    public function findById($id)
    {
        $accessData = [];
        $guest = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneBy([
                'id' => $id
            ]);

        if (!$guest) {
            return null;
        }

        $guestMySql = $this->em->getRepository('DomainBundle:Guests')
            ->findOneBy(['id' => $guest->getMysql()]);

        if ($guestMySql) {
            $devices = $this->guestDevices->getDevices($guestMySql);

            /**
             * @var DeviceEntry $item
             */
            foreach ($devices as $item) {
                array_push($accessData, $item->jsonSerialize_V1());
            }
        }

        $guest  = $this->convertGuestMongoDateToDate($guest);
        $guest  = $guest->jsonSerialize();

        $guest['accessData'] = $accessData;

        return $guest;
    }

    /**
     * @param $id
     * @return mixed|null
     */
    public function findById_v2($id)
    {
        $result = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneBy([
                'mysql' => (int)$id
            ]);

        if (!$result) {
            return null;
        }

        $result = $this->convertGuestMongoDateToDate($result);
        $result = $this->convertDateTimeToUnixTimestamp($result);
        $guest  = $result->jsonSerialize_v2();

        return $guest;
    }

    /**
     * @param $id
     * @return Guest|null
     */
    public function getUserById($id)
    {
        $repository = $this->mongo->getRepository('DomainBundle:Guest\Guest');
        $guest = $repository->find($id);
        if (is_null($guest)) {
            return null;
        }

        return $guest;
    }

    /**
     * @param $id
     * @return Guest|null
     */
    public function getGuestByMysql($id)
    {
        $repository = $this->mongo->getRepository('DomainBundle:Guest\Guest');
        $guest = $repository->findOneBy([
            'mysql' => $id
        ]);

        if (is_null($guest)) {
            return null;
        }

        return $guest;
    }

    public function getOneGuest(OneGuestQueryDto $oneGuestQueryDto = null)
    {
        if ($oneGuestQueryDto == null) {
            return null;
        }

        $query = [];

        if (!empty($oneGuestQueryDto->getProperty()) && !empty($oneGuestQueryDto->getValue())) {
            $query['properties.' . $oneGuestQueryDto->getProperty()] = $oneGuestQueryDto->getValue();
        }

        if (!empty($oneGuestQueryDto->getMysql())) {
            $query['mysql'] = (int) $oneGuestQueryDto->getMysql();
        }

        if (!empty($oneGuestQueryDto->getId())) {
            $query['id'] = $oneGuestQueryDto->getId();
        }

        if (empty($query)) {
            return null;
        }

        return $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneBy($query);
    }

    /**
     * @param Guest $guest
     * @return int|string
     */
    private function isFieldExists(Guest $guest)
    {
        $properties = $guest->getProperties();

        foreach ($properties as $key => $value) {
            $field = $this->customFieldsService->getFieldByNameType($key);

            if (!$field) {
                continue;
            }

            if (!$field->getIsUnique()) {
                continue;
            }

            $exists = $this->mongo
                ->getRepository('DomainBundle:Guest\Guest')
                ->findOneBy([
                    'properties.' . $key => new \MongoRegex('/^'.$properties[$key].'$/i')
                ]);

            if (!$exists || $exists->getId() == $guest->getId()) {
                continue;
            }

            return $key;
        }
    }

    private function getRegisterMode($registerMode)
    {
        if (!$registerMode) {
            return 'Formulário';
        }

        switch ($registerMode) {
            case Social::FACEBOOK:
                $registerMode = 'Facebook';
                break;
            case Social::TWITTER:
                $registerMode = 'Twitter';
                break;
            case Social::GOOGLE:
                $registerMode = 'Google';
                break;
            case Social::INSTAGRAM:
                $registerMode = 'Instagram';
                break;
            case Guest::REGISTER_BY_FORM:
                $registerMode = 'Formulário';
                break;
            case Social::LINKEDIN:
                $registerMode = 'LinkedIn';
                break;
            case Social::HUBSOFT:
                $registerMode = 'Hubsoft';
                break;
            case Social::IXC:
                $registerMode = 'Ixc';
                break;
        }

        return $registerMode;
    }

    /**
     * @param $identifier
     * @param $value
     * @return object|Guest
     */
    public function findGuestByProperty($identifier, $value)
    {
        return $this->mongo->getRepository('DomainBundle:Guest\Guest')
            ->findBy([
                "properties.{$identifier}" => $value
            ]);
    }

    /**
     * @param $identifier
     * @param $value
     * @return Guest
     */
    private function findOneGuestByProperty($identifier, $value)
    {
        return $this->mongo->getRepository('DomainBundle:Guest\Guest')
            ->findOneBy([
                "properties.{$identifier}" => $value
            ]);
    }

    /**
     * @return Guest
     */
    public function updateGuestProperties(Guest $guest)
    {
        $guest->setLoginField($this->customFieldsService->getLoginFieldIdentifier());

        $guestTemp = $this->findOneGuestByProperty($guest->getLoginField(), $guest->get($guest->getLoginField()));
        foreach($guest->getProperties() as $key => $value){
            if ($key == $guestTemp->getLoginField()) continue;
            $guestTemp->addProperty($key, $guest->get($key));
        }

        $this->mongo->persist($guestTemp);
        $this->mongo->flush();
        $this->mongo->refresh($guestTemp);
        return $guestTemp;
    }

    /**
     * @param Guest $guest
     * @return Guest
     */
    private function lowerCaseEmail(Guest $guest)
    {
        if (in_array('email', array_keys($guest->getProperties()))) {
            $propertites = $guest->getProperties();
            $propertites['email'] = strtolower($propertites['email']);
            $guest->setProperties($propertites);
        }
        return $guest;
    }

    private function parseStringBornDateToMongoDate(Guest $guest)
    {
        $properties = $guest->getProperties();
        if (key_exists('data_nascimento', $properties)) {
            $date = DateTime::createFromFormat("d/m/Y", $properties['data_nascimento']);
            $properties['data_nascimento'] = new MongoDate($date->getTimestamp());
        }
        $guest->setProperties($properties);
        return $guest;
    }

    /**
     * @param Client $client
     * @param DateTime $dateFrom
     * @param DateTime $dateTo
     * @param GuestAccessReportFilter $filter
     * @return GuestAccessReport[]
     */
    public function retrieveGuestsIds(
        Client $client,
        DateTime $dateFrom,
        DateTime $dateTo,
        GuestAccessReportFilter $filter
    ) {
        $dateFrom->setTime(0, 0, 0);
        $dateTo->setTime(23, 59, 59);
        $fieldToFilter  = $filter->getFieldToFilter();

        $guestsIds = $this->radacctReportService->retrieveGuestsIds(
            $client,
            $fieldToFilter,
            $dateFrom,
            $dateTo
        );

        if ($filter->getRecurrence()) {
            $is_unique = $filter->getRecurrence() === 'unique';
            $is_recurring = $filter->getRecurrence() === 'recurring';

            foreach ($guestsIds as $index => $guest) {
                if ($is_unique && $guest['doc_count'] > 1) {
                    unset($guestsIds[$index]);
                } elseif ($is_recurring && $guest['doc_count'] <= 1) {
                    unset($guestsIds[$index]);
                }
            }
        }
        return $guestsIds;
    }

    /**
     * @param Client $client
     * @param DateTime $dateFrom
     * @param DateTime $dateTo
     * @param GuestAccessReportFilter $filter
     * @return GuestAccessReport[]
     */
    public function getGuestInformationFromAccessDataReport(
        Client $client,
        DateTime $dateFrom,
        DateTime $dateTo,
        GuestAccessReportFilter $filter,
        $guestsIds = null
    ) {
        $dateFrom->setTime(0, 0, 0);
        $dateTo->setTime(23, 59, 59);
        $fieldToFilter  = $filter->getFieldToFilter();

        $accessData = $this->radacctReportService->getGuestAccessReport(
            $client,
            $fieldToFilter,
            $dateFrom,
            $dateTo,
            $filter->getRecurrence(),
            $guestsIds
        );

        return $accessData;
    }

    /**
     * @param $client
     * @return array
     * @throws \Exception
     */
    public function getAmountOfUniqueAndReturningGuestsLastMonth($client)
    {
        $dateFrom = new DateTime();
        $dateTo = new DateTime();

        $dateFrom->sub(new \DateInterval('P30D'));
        $dateFrom->setTime(0, 0, 0);
        $dateTo->setTime(23, 59, 59);

        try {
            $guests = $this->radacctReportService->retrieveGuestsIds(
                $client,
                'lastAccess',
                $dateFrom,
                $dateTo
            );

            $uniqueGuestsIds = array_filter($guests, function ($guest) {
                return $guest["doc_count"] === 1;
            });

            $uniqueGuestsIds = count($uniqueGuestsIds);
            $guests = count($guests);

            $recurringGuestsIds = $guests - $uniqueGuestsIds;
            return [
                'unique'    => $uniqueGuestsIds,
                'recurring' => $recurringGuestsIds,
            ];

        } catch (\Exception $ex) {
            $this->logger->addCritical('Fail to get amount of unique ou recurrent guests - ' . $ex->getMessage());
            throw new \Exception($ex);
        }
    }

    public function findGuestByMacAddressAndGuestId($guestMacAddress, $guestId)
    {
        if (!$guestId) {
            return false;
        }

        $client = $this->getLoggedClient();

        $hasGuest = $this->guestDevices->hasGuestByMacAddressAndGuestId($client, $guestMacAddress, $guestId);

        if (!$hasGuest) {
            return false;
        }

        return $this->guestRepository->findOneBy(['mysql' => $guestId]);
    }

    public function hasGuestByMacAddressAndGuestId($guestMacAddress, $guestId)
    {
        $search = $this->findGuestByMacAddressAndGuestId($guestMacAddress, $guestId);
        return boolval(count($search));
    }

    /**
     * @param Guest $guest
     * @return Guest
     */
    public function convertGuestMongoDateToDate(Guest $guest)
    {
        $properties = $guest->getProperties();
        foreach ($properties as $key => $property) {
            if (is_object($property) && $property instanceof MongoDate) {
                $date = date('d/m/Y', $property->sec);
                $properties[$key] = $date;
                $guest->setProperties($properties);
            }
        }
        return $guest;
    }

    private function convertDateTimeToUnixTimestamp(Guest $guest)
    {
        $guest->setCreated(DateTimeHelper::convertDateTimeToUnixTimestamp($guest->getCreated()));
        $guest->setLastAccess(DateTimeHelper::convertDateTimeToUnixTimestamp($guest->getLastAccess()));
        $guest->setValidated(DateTimeHelper::convertDateTimeToUnixTimestamp($guest->getValidated()));
        $guest->setEmailIsValidDate(DateTimeHelper::convertDateTimeToUnixTimestamp($guest->getEmailIsValidDate()));

        return $guest;
    }

    public function deleteGuestInAllBases(Client $client, $guest)
    {
        //ELASTIC
        $indexes = $this->radacctRepository->getAllIndexes($client->getId());

        /**
         * @var Guest $localGuest
         */
        $localGuest = $this->guestRepository->findOneBy(['id' => $guest['id']]);

        foreach ($indexes as $index) {
            try {
                $search = [
                    "size" => 9999,
                    "query" => [
                        "term" => [
                            "username" => $localGuest->getMysql()
                        ]
                    ]
                ];

                $accountings = $this->elasticSearchService->search('radacct', $search, $index['key']);


                if ($accountings['hits']['total'] > 0) {
                    $elasticSearchObject = [];

                    foreach ($accountings['hits']['hits'] as $accounting) {
                        array_push($elasticSearchObject, [
                            'delete' => [
                                '_index' => $index['key'],
                                '_type'  => 'radacct',
                                '_id'    => $accounting['_id']
                            ]
                        ]);
                    }

                    $this->elasticSearchService->bulk('radacct', $elasticSearchObject, $index['key']);
                }
            } catch (\Exception $e) {
                $this->logger->addError("Erro na deleção do visitante do Elastic: ".$e->getMessage());
            }
        }

        //MONGO
        try {
            $localGuest = $this->mongo->getRepository('DomainBundle:Guest\Guest')
                ->findBy(['_id' => new \MongoId($guest['id'])]);
            $localGuest = $localGuest[0];

            $this->mongo->remove($localGuest);
            $this->mongo->flush();
        } catch (\Exception $e) {
            $this->logger->addError("Erro na deleção do visitante do mongo: ".$e->getMessage());
        }

        //MYSQL
        try {
            $this->em->getRepository('DomainBundle:Guests')->deleteByGuest($localGuest->getMysql());
        } catch (\Exception $e) {
            $this->logger->addError("Erro na deleção do visitante do Mysql: ".$e->getMessage());
        }
    }

    public function confirmationIfPendingApproval(Guest $guest, Client $client, Nas $nas)
    {
        if ($this->configurationService->get($nas, $client, 'confirmation_email') != 1
                && $this->configurationService->get($nas, $client, 'confirmation_sms') != 1) {
            $guest->setStatus(Guest::STATUS_ACTIVE);
            $this->persist($guest);
            return true;
        }
        return false;
    }

    /**
     * @param Guest $guest
     * @throws MongoDuplicateKeyRegisterException
     */
    public function grantSignedConsent(Guest $guest) {
        if ($guest->isHasConsentRevoke()) {
            $guest->setHasConsentRevoke(false);
            $this->persist($guest);
        }
    }

    public function getGuestById($guestId)
    {
        return $this->mongo->getRepository('DomainBundle:Guest\Guest')
            ->findOneBy(['id' => $guestId]);
    }
    public function setSecretQuestionAnswerd(Guest $guest)
    {
        $guest->setHasSecurityAnswer(true);
        $this->mongo->merge($guest);
        $this->mongo->flush();
        $this->mongo->refresh($guest);
    }
}
