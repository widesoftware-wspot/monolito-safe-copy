<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Wideti\DomainBundle\Validator\Constraints as MyAssert;
use Wideti\WebFrameworkBundle\Entity\Embed\TimestampableEmbed;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table(name="clients")
 * @UniqueEntity(fields={"domain"}, message="Domínio já cadastrado na base de dados.")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\ClientRepository")
 */
class Client
{
    use TimestampableEmbed;

    const SESSION_KEY                   = 'wspotClient';

    const STATUS_INACTIVE               = 0;
    const STATUS_ACTIVE                 = 1;
    const STATUS_POC                    = 2;
    const STATUS_WAITING_FOR_PAYMENT    = 3;
    const STATUS_BLOCKED                = 4;

    const FAKE_MODE_OFF                 = 0;

    const TYPE_SIMPLE                   = 1;
    const TYPE_PROVIDER                 = 2;

    const CREATED_BY_AUTO_HIRING        = 'contratacao-automatica';
    const CREATED_BY_PANEL              = 'painel-azul';

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

	/**
	 * @ORM\ManyToOne(targetEntity="Wideti\DomainBundle\Entity\Segment", inversedBy="clients")
	 * @ORM\JoinColumn(name="segment_id", referencedColumnName="id")
	 */
	protected $segment;

	/**
	 * @ORM\ManyToOne(targetEntity="Wideti\DomainBundle\Entity\Plan", inversedBy="clients")
	 * @ORM\JoinColumn(name="plan_id", referencedColumnName="id")
	 */
	protected $plan;

    /**
     * @ORM\Column(name="erp_id", type="integer", nullable=true)
     */
    protected $erpId;

    /**
     * @ORM\Column(name="created_by", type="string", length=50, nullable=true)
     */
    protected $createdBy;

    /**
     * @ORM\Column(name="type", type="integer", options={"default":0} )
     */
    protected $type = 0;

    /**
     * @ORM\Column(name="status", type="integer", options={"default":1} )
     */
    protected $status = 1;

    /**
     * @ORM\Column(name="report_sent", type="integer", options={"default":0} )
     */
    protected $reportSent = 0;

    /**
     * @MyAssert\ClientDomainIsValid()
     * @ORM\Column(name="domain", type="string", length=100, unique=true)
     */
    protected $domain;

    /**
     * @ORM\Column(name="company", type="string", length=100)
     */
    protected $company;

    /**
     * @ORM\Column(name="document", type="string", length=100, nullable=true)
     */
    protected $document;

    /**
     * @ORM\Column(name="zip_code", type="string", length=10, nullable=true)
     */
    protected $zipCode;

    /**
     * @ORM\Column(name="address", type="string", length=200, nullable=true)
     */
    protected $address;

    /**
     * @ORM\Column(name="address_number", type="string", length=200, nullable=true)
     */
    protected $addressNumber;

    /**
     * @ORM\Column(name="address_complement", type="string", length=100, nullable=true)
     */
    protected $addressComplement;

    /**
     * @ORM\Column(name="district", type="string", length=100, nullable=true)
     */
    protected $district;

    /**
     * @ORM\Column(name="city", type="string", length=100, nullable=true)
     */
    protected $city;

    /**
     * @ORM\Column(name="state", type="string", length=2, nullable=true)
     */
    protected $state;

    /**
     * @ORM\Column(name="status_reason", type="string", nullable=true)
     */
    protected $statusReason;

    /**
     * @ORM\Column(name="cancellation_reason", type="string", nullable=true)
     */
    protected $cancellationReason;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="sms_cost", type="string", length=10)
     */
    protected $smsCost;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="contracted_access_points", type="string", length=10)
     */
    protected $contractedAccessPoints;

    /**
     * @ORM\Column(name="closing_date", type="string", length=2)
     */
    protected $closingDate;

    /**
     * @ORM\Column(name="poc_end_date", type="date", nullable=true)
     */
    protected $pocEndDate;

    /**
     * @ORM\Column(name="initial_setup", type="integer", options={"default":0} )
     */
    protected $initialSetup = 0;

    /**
     * @ORM\Column(name="fake_mode", type="integer", options={"default":0} )
     */
    protected $fakeMode = 0;

    /**
     * @ORM\Column(name="ap_check", type="integer", options={"default":1} )
     */
    protected $apCheck = 1;

    /**
     * @ORM\Column(name="pending_payment", type="integer", options={"default":0} )
     */
    protected $pendingPayment = 0;

    /**
     * @ORM\Column(name="change_plan_hash", type="string", length=100, nullable=true)
     */
    protected $changePlanHash;

    /**
     * @ORM\ManyToMany(targetEntity="Wideti\DomainBundle\Entity\Module", inversedBy="client", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="client_modules",
     *   joinColumns={
     *     @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="module_id", referencedColumnName="id")
     *   }
     * )
     */
    public $module;

    /**
     * @ORM\OneToMany(targetEntity="Wideti\DomainBundle\Entity\Users", mappedBy="client")
     */
    protected $users;

    /**
     * @ORM\Column(name="made_rd_integration", type="integer", options={"default":0} )
     */
    protected $madeRdIntegration = 0;

    /**
     * @ORM\Column(name="made_egoi_integration", type="integer", options={"default":0} )
     */
    protected $madeEgoiIntegration = 0;

    /**
     * @ORM\Column(name="enable_mac_authentication", type="integer", options={"default":0} )
     */
    protected $enableMacAuthentication = 0;

    /**
     * @ORM\Column(name="no_register_fields", type="integer", options={"default":0} )
     */
    protected $noRegisterFields = 0;

    /**
     * @ORM\Column(name="enable_password_authentication", type="boolean", options={"default":1} )
     */
    protected $enablePasswordAuthentication = true;

    /**
     * @ORM\OneToOne(targetEntity="DataControllerAgent", mappedBy="client")
     */
    protected $dataControllerAgent;

    /**
     * @ORM\Column(name="allow_fake_data", type="boolean", options={"default":0} )
     */
    protected $allowFakeData = false;

    /**
     * @ORM\Column(name="is_white_label", type="boolean", options={"default":0} )
     */
    protected $isWhiteLabel;

    /**
     * @ORM\OneToMany(targetEntity="Wideti\DomainBundle\Entity\ClientsLegalBase", mappedBy="client")
     */
    private $legalBases;

    /**
     * @ORM\Column(name="mongo_database_name", type="string", length=255, nullable=false)
     */
    private $mongoDatabaseName;

    /**
     * @ORM\Column(name="email_sender_default", type="string", length=255, nullable=false)
     */
    private $emailSenderDefault;

    // TODO: ESTE CAMPO $guestPasswordRecoverySecurity DEVERÁ SER REMOVIDO E DEVERÁ SER REPENSADO EM OUTRA SOLUÇÃO
    // TODO: QUANDO FOR SOLICITADO A IMPLEMENTAÇÃO DE OUTROS FATORES DE SEGURANÇA PARA A REDEFINIÇÃO DA SENHA
    // TODO: DO VISITANTE. ESTA IMPLEMENTAÇÃO FOI UMA SOLUÇÃO RÁPIDA PARA PERMITIR HABILITAR/DESABILITAR A SOLICITAÇÃO
    // TODO: DA PERGUNTA SECRETA, E DESTA FORMA, ESTE CAMPO ATENDE SOMENTE A ESTE MÉTODO DE SEGURANÇA
    /**
     * @ORM\Column(name="guest_password_recovery_security", type="boolean", nullable=false, options={"default":1})
     */
    private $guestPasswordRecoverySecurity = true;

    /**
     * @ORM\Column(name="guest_password_recovery_email", type="boolean", nullable=false, options={"default":0})
     */
    private $guestPasswordRecoveryEmail = false;

    /**
     * @ORM\Column(name="ask_guest_retroactive_fields", type="boolean", nullable=false, options={"default":0})
     */
    private $askRetroactiveGuestFields = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->module = new ArrayCollection();
    }

    public static function createClientWithId($id)
    {
        $client = new Client();
        $client->id = $id;
        return $client;
    }

    public function getStatusAsString()
    {
        $arrayStatus = $this->getStatusArray();

        switch ($this->status) {
            case self::STATUS_ACTIVE:
                return $arrayStatus[self::STATUS_ACTIVE];
                break;

            case self::STATUS_INACTIVE:
                return $arrayStatus[self::STATUS_INACTIVE];
                break;

            case self::STATUS_POC:
                return $arrayStatus[self::STATUS_POC];
                break;

            case self::STATUS_WAITING_FOR_PAYMENT:
                return $arrayStatus[self::STATUS_WAITING_FOR_PAYMENT];
                break;

            case self::STATUS_BLOCKED:
                return $arrayStatus[self::STATUS_BLOCKED];
        }
    }

    /**
     * @return array
     */
    public function getStatusArray()
    {
        return [
            self::STATUS_ACTIVE              => 'Ativo',
            self::STATUS_INACTIVE            => 'Inativo',
            self::STATUS_POC                 => 'Poc',
            self::STATUS_WAITING_FOR_PAYMENT => 'Aguardando pagamento',
            self::STATUS_BLOCKED             => 'Bloqueado',
        ];
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getErpId()
    {
        return $this->erpId;
    }

    /**
     * @param mixed $erpId
     */
    public function setErpId($erpId)
    {
        $this->erpId = $erpId;
    }

    /**
     * @return mixed
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @param mixed $createdBy
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param mixed $domain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * @return mixed
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param mixed $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * @return mixed
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param mixed $document
     */
    public function setDocument($document)
    {
        $this->document = $document;
    }

    /**
     * @return mixed
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * @param mixed $zipCode
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param mixed $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return mixed
     */
    public function getAddressNumber()
    {
        return $this->addressNumber;
    }

    /**
     * @param mixed $addressNumber
     */
    public function setAddressNumber($addressNumber)
    {
        $this->addressNumber = $addressNumber;
    }

    /**
     * @return mixed
     */
    public function getAddressComplement()
    {
        return $this->addressComplement;
    }

    /**
     * @param mixed $addressComplement
     */
    public function setAddressComplement($addressComplement)
    {
        $this->addressComplement = $addressComplement;
    }

    /**
     * @return mixed
     */
    public function getDistrict()
    {
        return $this->district;
    }

    /**
     * @param mixed $district
     */
    public function setDistrict($district)
    {
        $this->district = $district;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }


    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getReportSent()
    {
        return $this->reportSent;
    }

    /**
     * @param int $reportSent
     */
    public function setReportSent($reportSent)
    {
        $this->reportSent = $reportSent;
    }

    /**
     * @return string
     */
    public function getStatusReason()
    {
        return $this->statusReason;
    }

    /**
     * @param string $statusReason
     */
    public function setStatusReason($statusReason)
    {
        $this->statusReason = $statusReason;
    }

    /**
     * @return mixed
     */
    public function getCancellationReason()
    {
        return $this->cancellationReason;
    }

    /**
     * @param mixed $cancellationReason
     */
    public function setCancellationReason($cancellationReason)
    {
        $this->cancellationReason = $cancellationReason;
    }

    /**
     * @return mixed
     */
    public function getSmsCost()
    {
        return $this->smsCost;
    }

    /**
     * @param mixed $smsCost
     */
    public function setSmsCost($smsCost)
    {
        $this->smsCost = $smsCost;
    }

    /**
     * @return mixed
     */
    public function getContractedAccessPoints()
    {
        return $this->contractedAccessPoints;
    }

    /**
     * @param mixed $contractedAccessPoints
     */
    public function setContractedAccessPoints($contractedAccessPoints)
    {
        $this->contractedAccessPoints = $contractedAccessPoints;
    }

    /**
     * @return mixed
     */
    public function getClosingDate()
    {
        return $this->closingDate;
    }

    /**
     * @param mixed $closingDate
     */
    public function setClosingDate($closingDate)
    {
        $this->closingDate = $closingDate;
    }

    /**
     * @return mixed
     */
    public function getPocEndDate()
    {
        return $this->pocEndDate;
    }

    /**
     * @param mixed $pocEndDate
     */
    public function setPocEndDate($pocEndDate)
    {
        $this->pocEndDate = $pocEndDate;
    }

    /**
     * @return mixed
     */
    public function getInitialSetup()
    {
        return $this->initialSetup;
    }

    /**
     * @param mixed $initialSetup
     */
    public function setInitialSetup($initialSetup)
    {
        $this->initialSetup = $initialSetup;
    }

    /**
     * @return mixed
     */
    public function getFakeMode()
    {
        return $this->fakeMode;
    }

    /**
     * @param mixed $fakeMode
     */
    public function setFakeMode($fakeMode)
    {
        $this->fakeMode = $fakeMode;
    }

    /**
     * @return mixed
     */
    public function getApCheck()
    {
        return $this->apCheck;
    }

    /**
     * @param mixed $apCheck
     */
    public function setApCheck($apCheck)
    {
        $this->apCheck = $apCheck;
    }

    /**
     * @return mixed
     */
    public function getPendingPayment()
    {
        return $this->pendingPayment;
    }

    /**
     * @param mixed $pendingPayment
     */
    public function setPendingPayment($pendingPayment)
    {
        $this->pendingPayment = $pendingPayment;
    }

    /**
     * @return mixed
     */
    public function getChangePlanHash()
    {
        return $this->changePlanHash;
    }

    /**
     * @param mixed $changePlanHash
     */
    public function setChangePlanHash($changePlanHash)
    {
        $this->changePlanHash = $changePlanHash;
    }

    public function getMadeRdIntegration()
    {
        return $this->madeRdIntegration;
    }

    public function setMadeRdIntegration($madeRdIntegration)
    {
        $this->madeRdIntegration = $madeRdIntegration;
    }

    /**
     * @return mixed
     */
    public function getMadeEgoiIntegration()
    {
        return $this->madeEgoiIntegration;
    }

    /**
     * @param mixed $madeEgoiIntegration
     */
    public function setMadeEgoiIntegration($madeEgoiIntegration)
    {
        $this->madeEgoiIntegration = $madeEgoiIntegration;
    }

    /**
     * @return int
     */
    public function getEnableMacAuthentication()
    {
        return $this->enableMacAuthentication;
    }

    /**
     * @param int $enableMacAuthentication
     */
    public function setEnableMacAuthentication($enableMacAuthentication)
    {
        $this->enableMacAuthentication = $enableMacAuthentication;
    }

    /**
     * @return int
     */
    public function getNoRegisterFields()
    {
        return $this->noRegisterFields;
    }

    /**
     * @return string
     */
    public function getAuthenticationType()
    {
        if ($this->noRegisterFields) {
            return "no_register_fields";
        } elseif ($this->enablePasswordAuthentication) {
            return "enable_password_authentication";
        }
        return "disable_password_authentication";
    }

    /**
     * @param string $authenticationType
     */
    public function setAuthenticationType($authenticationType)
    {
        if ($authenticationType == "enable_password_authentication") {
            $this->enablePasswordAuthentication = 1;
            $this->noRegisterFields = 0;
        } elseif ($authenticationType == "disable_password_authentication") {
            $this->enablePasswordAuthentication = 0;
            $this->noRegisterFields = 0;
        } elseif ($authenticationType == "no_register_fields"){
            $this->enablePasswordAuthentication = 0;
            $this->noRegisterFields = 1;
        }
    }

    public function addModule(\Wideti\DomainBundle\Entity\Module $module)
    {
        $this->module[] = $module;

        return $this;
    }

    public function removeModule(\Wideti\DomainBundle\Entity\Module $module)
    {
        $this->module->removeElement($module);
    }

    public function getModules()
    {
        return $this->module;
    }

    /**
     * @return Users[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    public function __set($key, $value)
    {
        $this->$key = $value;
    }

    public function __get($key)
    {
        return $this->$key;
    }

    /**
     * @return mixed
     */
    public function getPlan()
    {
        return $this->plan;
    }

    /**
     * @param mixed $plan
     */
    public function setPlan($plan)
    {
        $this->plan = $plan;
    }

	/**
	 * @return mixed
	 */
	public function getSegment()
	{
		return $this->segment;
	}

	/**
	 * @param mixed $segment
	 */
	public function setSegment($segment)
	{
		$this->segment = $segment;
	}

    /**
     * @return mixed
     */
    public function isWhiteLabel()
    {
        return $this->isWhiteLabel;
    }

    /**
     * @param mixed $isWhiteLabel
     */
    public function setIsWhiteLabel($isWhiteLabel)
    {
        $this->isWhiteLabel = $isWhiteLabel;
    }

    /**
     * @return mixed
     */
    public function allowFakeData()
    {
        return $this->allowFakeData;
    }

    /**
     * @return bool
     */
    public function isEnablePasswordAuthentication()
    {
        return $this->enablePasswordAuthentication;
    }

    /**
     * @param bool $enablePasswordAuthentication
     */
    public function setEnablePasswordAuthentication($enablePasswordAuthentication)
    {
        $this->enablePasswordAuthentication = $enablePasswordAuthentication;
    }

    /**
     * @return PersistentCollection
     */
    public function getLegalBases()
    {
        return $this->legalBases;
    }

    /**
     * @return string
     */
    public function getMongoDatabaseName()
    {
        return $this->mongoDatabaseName;
    }

    /**
     * @param string $mongoDatabaseName
     */
    public function setMongoDatabaseName($mongoDatabaseName)
    {
        $this->mongoDatabaseName = $mongoDatabaseName;
    }

    /**
     * @param string $emailSenderDefault
     */
    public function setEmailSenderDefault($emailSenderDefault)
    {
        $this->emailSenderDefault = $emailSenderDefault;
    }

    public function getEmailSenderDefault()
    {
        return $this->emailSenderDefault;
    }

    public function toArray()
    {
        $client = [];
        foreach ($this as $field => $value){
            $client[$field] = $value;
        }
        unset($client["segment"]);
        unset($client["plan"]);
        return $client;
    }

    // TODO: ESTE MÉTODO DEVERÁ SER REMOVIDO JUNTAMENTE COM O CAMPO $guestPasswordRecoverySecurity QUANDO ESTE RECURSO
    // TODO: EVOLUÍDO. (VER //TODO ACIMA)
    /**
     * @return bool
     */
    public function hasGuestPasswordRecoverySecurity()
    {
        return $this->guestPasswordRecoverySecurity;
    }

    /**
     * @return bool
     */
    public function getGuestPasswordRecoveryEmail()
    {
        return $this->guestPasswordRecoveryEmail;
    }

    /**
     * @return bool
     */
    public function setGuestPasswordRecoveryEmail($guestPasswordRecoveryEmail)
    {
        $this->guestPasswordRecoveryEmail = $guestPasswordRecoveryEmail;
    }

    /**
     * @return bool
     */
    public function getGuestPasswordRecoverySecurity()
    {
        return $this->guestPasswordRecoverySecurity;
    }

    /**
     * @return bool
     */
    public function setGuestPasswordRecoverySecurity($guestPasswordRecoverySecurity)
    {
        $this->guestPasswordRecoverySecurity = $guestPasswordRecoverySecurity;
    }

    public function getAskRetroactiveGuestFields() {
        return $this->askRetroactiveGuestFields;
    }

    public function setAskRetroactiveGuestFields($askRetroactiveGuestFields) {
        $this->askRetroactiveGuestFields = $askRetroactiveGuestFields;
    }
}
