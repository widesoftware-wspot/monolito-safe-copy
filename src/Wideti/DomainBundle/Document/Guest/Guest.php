<?php

namespace Wideti\DomainBundle\Document\Guest;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\Bundle\MongoDBBundle\Validator\Constraints;
use Symfony\Component\Validator\Constraints as Assert;
use Wideti\DomainBundle\Service\Timezone\TimezoneService;
use Wideti\DomainBundle\Validator\Constraints\CustomFields;

/**
 * @ODM\Document(
 *      collection="guests",
 *      repositoryClass="Wideti\DomainBundle\Document\Repository\GuestRepository"
 * )
 */
class Guest implements \JsonSerializable
{
    const STATUS_INACTIVE           = 0;
    const STATUS_ACTIVE             = 1;
    const STATUS_PENDING_APPROVAL   = 2;
    const STATUS_BLOCKED            = 3;
    const STATUS_CONSENT_REVOKED    = 4;

    const REGISTER_BY_FORM          = 'Formulário';
    const REGISTER_BY_ADMIN         = 'Admin';
    const REGISTER_BY_API           = 'API';
    const REGISTER_BY_FACEBOOK      = 'Facebook';
    const REGISTER_BY_TWITTER       = 'Twitter';

    const PROPERTY_EMAIL = 'email';

    /**
     * @ODM\Id()
     */
    protected $id;

    /**
     * @ODM\Field(type="integer")
     */
    protected $mysql;

    /**
     * @ODM\Field(type="string")
     */
    protected $group;

    /**
     * @Assert\NotBlank(groups={"api"}, message="Senha é obrigatória")
     * @Assert\Length(groups={"api"}, min="6", minMessage="Senha deve ter no mínimo 6 caracteres")
     * @ODM\Field(type="string")
     */
    protected $password;

    /**
     * @ODM\Field(type="date")
     * @ODM\Index(order="asc")
     */
    protected $created;

    /**
     * @ODM\Field(type="date")
     */
    protected $validated;

    /**
     * @ODM\Field(type="date")
     */
    protected $lastAccess;

    /**
     * @ODM\Field(type="int")
     * @Assert\Choice(groups={"api", "api_update"}, choices={0,1}, message="O status do visitante deve ser 0 = inativo ou 1 = ativo")
     */
    protected $status;

    /**
     * @ODM\Field(type="boolean")
     * @var boolean
     */
    protected $emailIsValid = true;

    /**
     * @ODM\Field(type="date")
     */
    protected $emailIsValidDate;

    /**
     * @ODM\Field(type="string")
     */
    protected $locale;

    /**
     * @ODM\Field(type="string")
     */
    protected $documentType;

    /**
     * @ODM\Field(type="string")
     */
    protected $authorizeEmail;

    /**
     * @ODM\Field(type="string")
     */
    protected $registrationMacAddress;

    /**
     * @ODM\Field(type="boolean")
     */
    protected $returning = false;

    /**
     * @ODM\Field(type="string")
     */
    protected $registerMode;

    /**
     * @ODM\Field(type="hash")
     * @CustomFields(groups={"signUp"})
     */
    protected $properties;

    /**
     * @ODM\EmbedMany(targetDocument="Social")
     */
    protected $social = [];

    /**
     * @ODM\Field(type="hash")
     */
    protected $facebookFields;

    protected $loginField;

    /**
     * @ODM\Field(type="string")
     */
    protected $nasVendor;

    /**
     * @ODM\Field(type="string")
     */
    protected $nasRaw;

    /**
     * @ODM\Field(type="string")
     */
    private $utc;

    /**
     * @ODM\Field(type="string")
     */
    private $timezone;

    /**
     * @ODM\Field(type="string")
     */
    protected $lastPolicyIdCreated;

    /**
     * @ODM\Field(type="integer")
     */
    protected $countVisits;

    /**
     * @ODM\Field(type="boolean")
     */
    protected $hasConsentRevoke = false;

    /**
     * @ODM\Field(type="boolean")
     */
    protected $hasSecurityAnswer = false;

    /**
     * @ODM\Field(type="boolean")
     */
    protected $resetPassword = false;

    public function __construct()
    {
        $this->created      = new \MongoDate();
        $this->properties   = [];
        $this->facebookFields = [];
        $this->countVisits = 1;
    }

    public function addProperty($name, $value)
    {
        if ($name == '_id') {
            return;
        }
        $this->properties[$name] = $value;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param $key string
     * @return mixed | null
     */
    public function getPropertyByKey($key)
    {
        return isset($this->properties[$key]) ? $this->properties[$key] : null;
    }

    /**
     * @param $propertyPath
     * @return mixed|null
     */
    public function get($propertyPath)
    {
        if (!isset($this->properties[$propertyPath])) {
            return null;
        }
        return $this->properties[$propertyPath];
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getMysql()
    {
        return $this->mysql;
    }

    public function setMysql($mysql)
    {
        $this->mysql = $mysql;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return mixed
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param mixed $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    public function documentTypeFromLocale($locale)
    {
        switch (strtolower($locale)) {
            case 'pt_br':
                return 'CPF';
                break;
            default:
                return 'Passport';
                break;
        }
    }

    /**
     * Set created
     *
     * @param date $created
     * @return self
     */
    public function setCreated($created)
    {
        $this->created = $created;
        return $this;
    }

    /**
     * Get created
     *
     * @return \MongoDate $created
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set validated
     *
     * @param date $validated
     * @return self
     */
    public function setValidated($validated)
    {
        $this->validated = $validated;
        return $this;
    }

    /**
     * Get validated
     *
     * @return date $validated
     */
    public function getValidated()
    {
        return $this->validated;
    }

    /**
     * Set lastAccess
     *
     * @param  $lastAccess
     * @return self
     */
    public function setLastAccess($lastAccess)
    {
        $this->lastAccess = $lastAccess;
        return $this;
    }

    /**
     * Get lastAccess
     *
     * @return  $lastAccess
     */
    public function getLastAccess()
    {
        return $this->lastAccess;
    }

    /**
     * Set status
     *
     * @param int $status
     * @return self
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get status
     *
     * @return int $status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return boolean
     */
    public function getEmailIsValid()
    {
        return $this->emailIsValid;
    }

    /**
     * @param boolean $emailIsValid
     */
    public function setEmailIsValid($emailIsValid)
    {
        $this->emailIsValid = $emailIsValid;
    }

    /**
     * Set emailIsValidDate
     *
     * @param  $emailIsValidDate
     * @return self
     */
    public function setEmailIsValidDate($emailIsValidDate)
    {
        $this->emailIsValidDate = $emailIsValidDate;
        return $this;
    }

    /**
     * Get emailIsValidDate
     *
     * @return  $emailIsValidDate
     */
    public function getEmailIsValidDate()
    {
        return $this->emailIsValidDate;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return self
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Get locale
     *
     * @return string $locale
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set documentType
     *
     * @param string $documentType
     * @return self
     */
    public function setDocumentType($documentType)
    {
        $this->documentType = $documentType;
    }

    /**
     * Get documentType
     *
     * @return string $documentType
     */
    public function getDocumentType()
    {
        return $this->documentType;
    }

    /**
     * Set authorizeEmail
     *
     * @param int $authorizeEmail
     * @return self
     */
    public function setAuthorizeEmail($authorizeEmail)
    {
        $this->authorizeEmail = $authorizeEmail;
        return $this;
    }

    /**
     * Get authorizeEmail
     *
     * @return int $authorizeEmail
     */
    public function getAuthorizeEmail()
    {
        return $this->authorizeEmail;
    }

    /**
     * Set registrationMacAddress
     *
     * @param string $registrationMacAddress
     * @return self
     */
    public function setRegistrationMacAddress($registrationMacAddress)
    {
        $this->registrationMacAddress = $registrationMacAddress;
        return $this;
    }

    /**
     * Get registrationMacAddress
     *
     * @return string $registrationMacAddress
     */
    public function getRegistrationMacAddress()
    {
        return $this->registrationMacAddress;
    }

    /**
     * Set returning
     *
     * @param boolean $returning
     * @return self
     */
    public function setReturning($returning)
    {
        $this->returning = $returning;
        return $this;
    }

    /**
     * Get returning
     *
     * @return boolean $returning
     */
    public function getReturning()
    {
        return $this->returning;
    }

    /**
     * Set properties
     *
     * @param array $properties
     * @return self
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSocial()
    {
        return $this->social;
    }

    /**
     * @param mixed $social
     */
    public function addSocial(Social $social)
    {
        $this->social[] = $social;
    }

    /**
     * @return mixed
     */
    public function getFacebookFields()
    {
        return $this->facebookFields;
    }

    /**
     * @param mixed $facebookFields
     */
    public function setFacebookFields($facebookFields)
    {
        $this->facebookFields = $facebookFields;
    }

    public function getStatusAsString()
    {
        switch ($this->status) {
            case self::STATUS_PENDING_APPROVAL:
                return "Pendente de confirmação";
                break;
            case self::STATUS_ACTIVE:
                return "Ativo";
                break;
            case self::STATUS_INACTIVE:
                return "Inativo";
                break;
            case self::STATUS_BLOCKED:
                return "Bloqueado por tempo";
                break;
            default:
                return "Ativo";
        }
    }

    public function getStatusAsEnum()
    {
        switch ($this->status) {
            case self::STATUS_PENDING_APPROVAL:
                return "PENDING_APPROVAL";
                break;
            case self::STATUS_ACTIVE:
                return "ACTIVE";
                break;
            case self::STATUS_INACTIVE:
                return "INACTIVE";
                break;
            case self::STATUS_BLOCKED:
                return "BLOCKED";
                break;
            default:
                return "ACTIVE";
        }
    }

    public function getCountVisits()
    {
        return $this->countVisits;
    }

    public function addVisit()
    {
        $this->countVisits++;
    }

    /**
     * @return bool
     */
    public function isHasConsentRevoke()
    {
        return $this->hasConsentRevoke;
    }

    /**
     * @param $hasConsentRevoke
     * @return $this
     */
    public function setHasConsentRevoke($hasConsentRevoke)
    {
        $this->hasConsentRevoke = $hasConsentRevoke;
        return $this;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $properties = [];
        $fields = get_class_vars(get_class($this));

        foreach ($fields as $key => $value) {
            $objPropertiesValues = get_object_vars($this);
            $properties[$key] = $objPropertiesValues[$key];
        }

        $properties['refId'] = $properties['mysql'];

        unset($properties['mysql']);
        unset($properties['password']);
        return $properties;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize_v2()
    {
        $properties = [];
        $fields = get_class_vars(get_class($this));

        foreach ($fields as $key => $value) {
            $objPropertiesValues = get_object_vars($this);
            $properties[$key] = $objPropertiesValues[$key];
        }

        $properties['id']       = $properties['mysql'];
        $properties['status']   = $this->getStatusAsEnum();

        unset($properties['mysql']);
        unset($properties['password']);
        unset($properties['loginField']);
        unset($properties['nasVendor']);
        unset($properties['nasRaw']);
        unset($properties['utc']);
        unset($properties['timezone']);
        unset($properties['lastPolicyIdCreated']);
        unset($properties['accessData']);
        return $properties;
    }

    public function __set($property, $value)
    {
        $this->$property = $value;
    }

    public function __get($property)
    {
        return $this->$property;
    }

    /**
     * @return mixed
     */
    public function getRegisterMode()
    {
        return $this->registerMode;
    }

    /**
     * @param mixed $registerMode
     */
    public function setRegisterMode($registerMode)
    {
        $this->registerMode = $registerMode;
    }

    /**
     * @return mixed
     */
    public function getLoginField()
    {
        return $this->loginField;
    }

    /**
     * @param mixed $loginField
     */
    public function setLoginField($loginField)
    {
        $this->loginField = $loginField;
    }

    /**
     * @param mixed $nasVendor
     */
    public function setNasVendor($nasVendor)
    {
        $this->nasVendor = $nasVendor;
    }

    /**
     * @return mixed
     */
    public function getNasVendor()
    {
        return $this->nasVendor;
    }

    /**
     * @return mixed
     */
    public function getNasRaw()
    {
        return $this->nasRaw;
    }

    /**
     * @param mixed $nasRaw
     */
    public function setNasRaw($nasRaw)
    {
        $this->nasRaw = $nasRaw;
    }

    /**
     * @return mixed
     */
    public function getUtc()
    {
        return $this->utc;
    }

    /**
     * @param $utc
     */
    public function setUtc($utc)
    {
        (is_null($utc) || empty($utc)) ? $this->utc = '(UTC-03:00) Brasilia' : $this->utc = $utc;
    }

    /**
     * @return mixed
     */
    public function getTimezone()
    {
        $timezone = $this->timezone;
        $timezone = is_null($timezone) ? TimezoneService::DEFAULT_TIMEZONE : $this->timezone;
        return $timezone;
    }

    /**
     * @param mixed $timezone
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * @return mixed
     */
    public function getLastPolicyIdCreated()
    {
        return $this->lastPolicyIdCreated;
    }

    /**
     * @param mixed $lastPolicyIdCreated
     */
    public function setLastPolicyIdCreated($lastPolicyIdCreated)
    {
        $this->lastPolicyIdCreated = $lastPolicyIdCreated;
    }

    public static function getRandomRegisterMode()
    {
        $modes = [
            self::REGISTER_BY_FORM,
            self::REGISTER_BY_ADMIN,
            self::REGISTER_BY_API,
            self::REGISTER_BY_FACEBOOK,
            self::REGISTER_BY_TWITTER
        ];

        $mode = array_rand($modes, 1);

        return $modes[$mode];
    }

    /**
     * @param bool $hasSecurityAnswer
     */
    public function setHasSecurityAnswer($hasSecurityAnswer)
    {
        $this->hasSecurityAnswer = $hasSecurityAnswer;
        return $this;
    }

    /**
     * @return bool
     */
    public function isHasSecurityAnswer()
    {
        return $this->hasSecurityAnswer;
    }

    /**
     * @param bool $resetPassword
     */
    public function setResetPassword($resetPassword)
    {
        $this->resetPassword = $resetPassword;
    }

    /**
     * @return bool
     */
    public function getResetPassword()
    {
        return $this->resetPassword;
    }

}

