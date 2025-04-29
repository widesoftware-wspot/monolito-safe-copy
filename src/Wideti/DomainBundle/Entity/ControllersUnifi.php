<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Table(
 *      name="controllers_unifi"
 * )
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\ControllersUnifiRepository")
 */
class ControllersUnifi
{
    const INACTIVE = 0;
    const ACTIVE   = 1;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Type option
     * @ORM\Column(name="address", type="string", length=100, nullable=false)
     */
    private $address;

    /**
     * Type option
     * @ORM\Column(name="port", type="integer", nullable=false, options={"default":8443})
     */
    private $port;

    /**
     * Type option
     * @ORM\Column(name="username", type="string", length=50, nullable=false)
     */
    private $username;

    /**
     * Type option
     * @ORM\Column(name="password", type="string", length=50, nullable=false)
     */
    private $password;

    /**
     * @ORM\Column(name="is_mambo", type="boolean", options={"default":0} )
     */
    protected $isMambo = self::INACTIVE;

    /**
     * @ORM\Column(name="is_active", type="boolean", options={"default":1} )
     */
    protected $active = self::ACTIVE;

    /**
     * Type option
     * @ORM\Column(name="comments", type="string", length=255, nullable=true)
     */
    private $comments;

    /**
     * ControllersUnifi constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param $address
     * @return $this
     */
    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param $port
     * @return $this
     */
    public function setPort($port)
    {
        $this->port = $port;
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
     * @param $username
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return int
     */
    public function getIsMambo()
    {
        return $this->isMambo;
    }

    /**
     * @return int|string
     */
    public function getIsMamboAsString()
    {
        switch ($this->isMambo) {
            case self::INACTIVE:
                return "Não";
                break;
            case self::ACTIVE:
                return "Sim";
                break;
            default:
                return self::ACTIVE;
        }
    }

    /**
     * @param $isMambo
     * @return $this
     */
    public function setIsMambo($isMambo)
    {
        $this->isMambo = $isMambo;
        return $this;
    }

    /**
     * @return int
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @return int|string
     */
    public function getActiveAsString()
    {
        switch ($this->active) {
            case self::INACTIVE:
                return "Inativo";
                break;
            case self::ACTIVE:
                return "Ativo";
                break;
            default:
                return self::ACTIVE;
        }
    }

    /**
     * @param $active
     * @return $this
     */
    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }
}
