<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Wideti\WebFrameworkBundle\Entity\Embed\TimestampableEmbed;

/**
 * @ORM\Table(name="api_wspot")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\ApiWSpotRepository")
 */
class ApiWSpot implements AdvancedUserInterface
{
    use TimestampableEmbed;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

	/**
	 * @ORM\ManyToOne(targetEntity="Wideti\DomainBundle\Entity\Client")
	 * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
	 */
	protected $client;

	/**
	 * @ORM\Column(name="name", type="string", length=255, nullable=true)
	 */
	private $name;

	/**
	 * @ORM\Column(name="token", type="string", length=255, nullable=true)
	 */
	private $token;

    /**
     * @var int
     */
    private $permissionType;

    /**
     * @ORM\OneToMany(targetEntity="Wideti\DomainBundle\Entity\ApiWSpotRoles", mappedBy="api", cascade={"remove"})
     */
    protected $roles;

    /**
     * @ORM\OneToMany(targetEntity="Wideti\DomainBundle\Entity\ApiWSpotResources", mappedBy="api", cascade={"remove"})
     */
    protected $resources;

    /**
     * @ORM\OneToMany(targetEntity="Wideti\DomainBundle\Entity\ApiWSpotContracts", mappedBy="api", cascade={"remove"})
     */
    protected $contracts;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param mixed $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return int
     */
    public function getPermissionType()
    {
        return $this->permissionType;
    }

    public function setPermissionType($permissionType)
    {
        $this->permissionType = $permissionType;
    }

    public function addPermissionType()
    {
        $entity = $this;
        $resources = $entity->getResources();
        $methods = [];

        foreach ($resources as $resource) {
            array_push($methods, $resource->getMethod());
        }

        if (in_array('POST', $methods)) {
            $this->setPermissionType(ApiWSpotRoles::ROLE_WRITE);
        } else {
            $this->setPermissionType(ApiWSpotRoles::ROLE_READ);
        }
    }

    /**
     * @return mixed
     */
    public function getRoles()
    {
        $roles = [];
        if ($this->roles) {
            foreach ($this->roles as $role) {
                array_push($roles, $role->getRole());
            }
        }
        return $roles;
    }

    /**
     * @return mixed
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * @return mixed
     */
    public function getContracts()
    {
        return $this->contracts;
    }

    /**
     * Checks whether the user's account has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw an AccountExpiredException and prevent login.
     *
     * @return bool true if the user's account is non expired, false otherwise
     *
     * @see AccountExpiredException
     */
    public function isAccountNonExpired()
    {
        return true;
    }

    /**
     * Checks whether the user is locked.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a LockedException and prevent login.
     *
     * @return bool true if the user is not locked, false otherwise
     *
     * @see LockedException
     */
    public function isAccountNonLocked()
    {
        return true;
    }

    /**
     * Checks whether the user's credentials (password) has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a CredentialsExpiredException and prevent login.
     *
     * @return bool true if the user's credentials are non expired, false otherwise
     *
     * @see CredentialsExpiredException
     */
    public function isCredentialsNonExpired()
    {
        return true;
    }

    /**
     * Checks whether the user is enabled.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a DisabledException and prevent login.
     *
     * @return bool true if the user is enabled, false otherwise
     *
     * @see DisabledException
     */
    public function isEnabled()
    {
        return true;
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The password
     */
    public function getPassword()
    {
        return "";
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername()
    {
        return $this->token;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
    }
}
