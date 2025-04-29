<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\MaxDepth;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Google\Authenticator\GoogleAuthenticator;
use Wideti\DomainBundle\Entity\UrlResetPassword;

/**
 * @ORM\Table(
 *      name="usuarios",
 *      uniqueConstraints={
 *          @UniqueConstraint(
 *              name="unique_user",
 *              columns={
 *                  "username",
 *                  "client_id"
 *              }
 *          )
 *      }
 * )
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\UsersRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Users implements AdvancedUserInterface, \Serializable
{
    // ROLE_SUPER_ADMIN Somente usuário da wide: contato@wideti.com.br
    const ROLE_SUPER_ADMIN          = 1;
    const ROLE_ADMIN                = 2;
    const ROLE_USER                 = 3;
    const ROLE_MARKETING            = 4;
    const ROLE_MANAGER              = 5;
    const ROLE_USER_LIMITED         = 6;
    const ROLE_MARKETING_LIMITED    = 10;
    const ROLE_ADMIN_LIMITED        = 11;
    const ROLE_SUPORT_LIMITED       = 12;
    const ROLE_USER_BASIC           = 13;

    const INACTIVE          = 0;
    const ACTIVE            = 1;
    const DELETED           = 2;

    const USER_DEFAULT      = 'contato@wideti.com.br';
    const USER_ADMIN        = 'admin@wideti.com.br';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="idp_id", type="string", length=100, nullable=true)
     */
    private $idpId;

	/**
	 * @ORM\Column(name="spot_manager", type="boolean")
	 */
    private $spotManager;

	/**
	 * @ORM\Column(name="spot_manager_logged", type="boolean")
	 */
    private $spotManagerLogged;

    /**
     * @ORM\Column(name="username", type="string", length=100)
     * @Assert\Email(
     *    message   = "Email inválido.",
     *    checkMX   = true,
     *    checkHost = true
     *  )
     */
    private $username;

    /**
     * @ORM\Column(name="nome", type="string", length=100)
     * @Assert\Length(
     *      min = "2",
     *      max = "50",
     *      minMessage = "Seu nome deve conter pelo menos {{ limit }} caracteres",
     *      maxMessage = "Seu nome deve conter no máximo {{ limit }} caracteres"
     * )
     */
    private $nome;

    /**
     * @ORM\Column(name="password", type="string", length=100)
     */
    private $password;
    private $plainPassword;

    /**
     * @ORM\Column(name="ultimo_acesso", type="datetime", nullable=true)
     */
    private $ultimoAcesso;

    /**
     * @ORM\Column(name="status", type="integer")
     * @Assert\Choice(
     *      choices = { 0, 1, 2},
     *      message = "Status não permitido."
     *  )
     */
    private $status;

    private $autoPassword;

    /**
     * @ORM\Column(name="data_cadastro", type="datetime")
     */
    private $dataCadastro;

    /**
     * @ORM\Column(name="deletedAt", type="datetime", nullable=true)
     * @var \DateTime
     */
    private $deletedAt;

    /**
     * @ORM\Column(name="salt", type="string", length=32)
     */
    private $salt;

    /**
     * @ORM\Column(name="receive_report_mail", type="boolean")
     */
    private $receiveReportMail;

    /**
     * @ORM\Column(name="report_mail_language", type="boolean")
     */
    private $reportMailLanguage;

    /**
     * User's roles. (Owning Side)
     * @ORM\ManyToOne(targetEntity="Roles", inversedBy="users")
     * @ORM\JoinColumn(name="role_id", referencedColumnName="id")
     */
    private $role;

    /**
     * @ORM\Column(name="financial_manager", type="boolean")
     */
    private $financialManager;

    /**
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="users")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     * @Exclude()
     */
    protected $client;

    /**
     * @ORM\OneToMany(targetEntity="ContractUser", mappedBy="user")
     * @Exclude()
     */
    private $contracts;

    /**
     * @ORM\Column(name="created_at_idp", type="boolean", options={"default":0} )
     */
    private $createdAtIdp;

    /**
     * @ORM\Column(name="created_at_oauth", type="boolean", options={"default":0} )
     */
    private $createdAtOauth = 0;

    /**
     * @ORM\Column(name="erp_id", type="integer", nullable=true)
     */
    protected $erpId;

    /**
     * @ORM\Column(name="two_factor_authentication_enabled", type="integer", options={"default":0} )
     */
    private $twoFactorAuthenticationEnabled;

    /**
     * @ORM\Column(name="two_factor_authentication_secret", type="string", length=100, nullable=true)
     */
    private $twoFactorAuthenticationSecret;

    /**
     * @ORM\OneToOne(
     *      targetEntity="Wideti\DomainBundle\Entity\UsersTokensAuth",
     *      mappedBy="user",
     *      cascade={"persist", "remove"}
     * )
     */
    private $userTokenAuth;

    /**
     * @ORM\OneToOne(targetEntity="Wideti\DomainBundle\Entity\UrlResetPassword", mappedBy="user")
     */
    protected $urlsResetPassword;

      /**
     * @ORM\Column(name="reseted_to_strong_password", type="boolean")
     */
    private $resetedToStrongPassword = false;

    public function __construct()
    {
        // TODO estou colocando como enviado na mão por padrão. Tiraremos isso em breve.
        $this->setCreatedAtIdp(true);
        $this->salt      = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
        $this->contracts = new \Doctrine\Common\Collections\ArrayCollection();

        $this->spotManager = false;
        $this->spotManagerLogged = false;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getIdpId()
    {
        return $this->idpId;
    }

    /**
     * @param mixed $idpId
     */
    public function setIdpId($idpId)
    {
        $this->idpId = $idpId;
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
     *  @ORM\PrePersist
     *
     */
    public function setCreatedAtValue()
    {
        $this->setDataCadastro(new \DateTime());

        return $this;
    }

    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isEnabled()
    {
        return $this->status;
    }

    public function eraseCredentials()
    {
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function getStatusAsString()
    {
        switch ($this->getStatus()) {
            case 1:
                return 'Ativo';
                break;
            case 0:
                return 'Inativo';
                break;
            default:
                return 'NotFound';
                break;
        }
    }

    public function getReceiveReportMailAsString()
    {
        switch ($this->getStatus()) {
            case 1:
                return 'Sim';
                break;
            case 0:
                return 'Não';
                break;
            default:
                return 'NotFound';
                break;
        }
    }

    public function getReportMailLanguageAsString()
    {
        switch ($this->getReportMailLanguage()) {
            case 0:
                return 'Português';
                break;
            case 1:
                return 'Inglês';
                break;
            default:
                return 'Português';
                break;
        }
    }

    public function getSalt()
    {
        return $this->salt;
    }

    public function getRoles()
    {
        return array($this->getRole());
    }

    /**
     * Set username
     *
     * @param  string $username
     * @return Users
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set nome
     *
     * @param  string $nome
     * @return Users
     */
    public function setNome($nome)
    {
        $this->nome = $nome;

        return $this;
    }

    /**
     * Get nome
     *
     * @return string
     */
    public function getNome()
    {
        return $this->nome;
    }

    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set password
     *
     * @param  string $password
     * @return Users
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * @param mixed $plainPassword
     */
    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;
    }

    /**
     * Set ultimoAcesso
     *
     * @param  \DateTime $ultimoAcesso
     * @return Users
     */
    public function setUltimoAcesso($ultimoAcesso)
    {
        $this->ultimoAcesso = $ultimoAcesso;

        return $this;
    }

    /**
     * Get ultimoAcesso
     *
     * @return \DateTime
     */
    public function getUltimoAcesso()
    {
        return $this->ultimoAcesso;
    }

    /**
     * Set status
     *
     * @param  string $status
     * @return Users
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getAutoPassword()
    {
        return $this->autoPassword;
    }

    /**
     * @param mixed $autoPassword
     */
    public function setAutoPassword($autoPassword)
    {
        $this->autoPassword = $autoPassword;
    }

    /**
     * Set dataCadastro
     *
     * @param  \DateTime $dataCadastro
     * @return Users
     */
    public function setDataCadastro($dataCadastro)
    {
        $this->dataCadastro = $dataCadastro;

        return $this;
    }

    /**
     * Get dataCadastro
     *
     * @return \DateTime
     */
    public function getDataCadastro()
    {
        return $this->dataCadastro;
    }

    /**
     * Set salt
     *
     * @param  string $salt
     * @return Users
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Set role
     *
     * @param  \Wideti\DomainBundle\Entity\Roles $role
     * @return Users
     */
    public function setRole(\Wideti\DomainBundle\Entity\Roles $role = null)
    {
        $this->role = $role;

        return $this;
    }

    public function setRoles($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return \Wideti\DomainBundle\Entity\Roles
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @see \Serializable::serialize()
     */
    public function serialize()
    {
        return \serialize(
            array(
            $this->id,
            $this->username,
            $this->nome,
            $this->password,
            $this->ultimoAcesso,
            $this->status,
            $this->dataCadastro,
            $this->salt
            )
        );
    }

    /**
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->username,
            $this->nome,
            $this->password,
            $this->ultimoAcesso,
            $this->status,
            $this->dataCadastro,
            $this->salt,
            ) = \unserialize($serialized);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->nome;
    }

    public function setClient(\Wideti\DomainBundle\Entity\Client $client)
    {
        $this->client = $client;

        return $this;
    }

    public function getClient()
    {
        return $this->client;
    }

    /**
     * Add signatures
     *
     * @param \Wideti\DomainBundle\Entity\ContractUser $contracts
     * @return Contract
     */
    public function addContract(\Wideti\DomainBundle\Entity\ContractUser $contracts)
    {
        $this->contracts[] = $contracts;

        return $this;
    }

    /**
     * Remove signatures
     *
     * @param \Wideti\DomainBundle\Entity\ContractUser $contracts
     */
    public function removeContract(\Wideti\DomainBundle\Entity\ContractUser $contracts)
    {
        $this->contracts->removeElement($contracts);
    }

    /**
     * Get signatures
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getContracts()
    {
        return $this->contracts;
    }

    /**
     * @return mixed
     */
    public function getReceiveReportMail()
    {
        return $this->receiveReportMail;
    }

    /**
     * @param mixed $receiveReportMail
     */
    public function setReceiveReportMail($receiveReportMail)
    {
        $this->receiveReportMail = $receiveReportMail;
    }

    /**
     * @return mixed
     */
    public function getReportMailLanguage()
    {
        return $this->reportMailLanguage;
    }

    /**
     * @param mixed $reportMailLanguage
     */
    public function setReportMailLanguage($reportMailLanguage)
    {
        $this->reportMailLanguage = $reportMailLanguage;
    }

    /**
     * @return mixed
     */
    public function getFinancialManager()
    {
        return $this->financialManager;
    }

    /**
     * @param mixed $financialManager
     */
    public function setFinancialManager($financialManager)
    {
        $this->financialManager = $financialManager;
    }

    /**
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * @param \DateTime $deletedAt
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }

    /**
     * @return mixed
     */
    public function isCreatedAtIdp()
    {
        return (boolean)$this->createdAtIdp;
    }

    /**
     * @return mixed
     */
    public function isCreatedAtOauth()
    {
        return (boolean)$this->createdAtOauth;
    }

    /**
     * @param mixed $createdAtIdp
     */
    public function setCreatedAtIdp($createdAtIdp)
    {
        $this->createdAtIdp = $createdAtIdp;
    }

    /**
     * @param mixed $createdAtOauth
     */
    public function setCreatedAtOauth($createdAtOauth)
    {
        $this->createdAtOauth = $createdAtOauth;
    }

    /**
     * @return mixed
     * Authentication
     */
    public function hasTwoFactorAuthenticationEnabled()
    {
        if ($this->twoFactorAuthenticationEnabled == 1)
            return true;
        else
            return false;
    }

    /**
     * @param mixed $twoFactorAuthenticationEnabled
     */
    public function setTwoFactorAuthenticationEnabled($twoFactorAuthenticationEnabled)
    {
        $this->twoFactorAuthenticationEnabled = $twoFactorAuthenticationEnabled;
    }

    /**
     * @param mixed $twoFactorAuthenticationSecret
     */
    public function setTwoFactorAuthenticationSecret($twoFactorAuthenticationSecret) {

        $this->twoFactorAuthenticationSecret = $twoFactorAuthenticationSecret;
    }

    /**
     * @param
     */
    public function resetTwoFactorAuthenticationSecret() {
        $this->setTwoFactorAuthenticationSecret("");
    }

    /**
     * @return mixed
     */
    public function getTwoFactorAuthenticationSecret()
    {
        if ($this->twoFactorAuthenticationSecret == "") {
            $g = new GoogleAuthenticator();
            $this->setTwoFactorAuthenticationSecret($g->generateSecret());
        }

        return $this->twoFactorAuthenticationSecret;
    }

	/**
	 * @return boolean
	 */
	public function isSpotManager()
	{
		return $this->spotManager;
	}

	/**
	 * @param boolean $spotManager
	 */
	public function setIsSpotManager($spotManager)
	{
		$this->spotManager = $spotManager;
	}

	/**
	 * @return boolean
	 */
	public function getSpotManagerLogged()
	{
		return $this->spotManagerLogged;
	}

	/**
	 * @param boolean $spotManagerLogged
	 */
	public function setSpotManagerLogged($spotManagerLogged)
	{
		$this->spotManagerLogged = $spotManagerLogged;
	}

    /**
     * @return UsersTokensAuth
     */
    public function getUserTokenAuth()
    {
        return $this->userTokenAuth;
    }

    public function setUserTokenAuth(UsersTokensAuth $userTokenAuth)
    {
        $this->userTokenAuth = $userTokenAuth;
    }

     /**
     * @return UrlResetPassword[]
     */
    public function getUrlsResetPassword()
    {
        return $this->urlsResetPassword;
    }

    public function getResetedToStrongPassword()
    {
        return $this->resetedToStrongPassword;
    }

    public function setResetedToStrongPassword($resetedToStrongPassword) {
        $this->resetedToStrongPassword = $resetedToStrongPassword;
    }
}
