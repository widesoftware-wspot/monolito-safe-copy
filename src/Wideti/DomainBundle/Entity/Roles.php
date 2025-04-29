<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table(name="roles")
 * @ORM\Entity()
 */
class Roles implements RoleInterface, \Serializable
{
    const MANAGER = 'Manager';
    const ROLE_MANAGER = 'ROLE_MANAGER';
    const SUPORT_LIMITED = 'Suporte - Limitado';
    const ROLE_SUPORT_LIMITED = 'ROLE_SUPORT_LIMITED';
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="string", length=100)
     */
    protected $name;

    /**
     * @ORM\Column(name="role", type="string", length=100)
     */
    protected $role;

    /**
     * @ORM\OneToMany(targetEntity="Wideti\DomainBundle\Entity\Users", mappedBy="role")
     * @Exclude()
     */
    protected $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function getRole()
    {
        return $this->role;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param  string $name
     * @return Roles
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set role
     *
     * @param  string $role
     * @return Roles
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Add users
     *
     * @param  \Wideti\DomainBundle\Entity\Users $users
     * @return Roles
     */
    public function addUser(\Wideti\DomainBundle\Entity\Users $users)
    {
        $this->users[] = $users;

        return $this;
    }

    /**
     * Remove users
     *
     * @param \Wideti\DomainBundle\Entity\Users $users
     */
    public function removeUsuario(\Wideti\DomainBundle\Entity\Users $users)
    {
        $this->users->removeElement($users);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @see \Serializable::serialize()
     */
    public function serialize()
    {
        return \serialize(array(
            $this->id,
            $this->name,
            $this->role,
        ));
    }

    /**
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->name,
            $this->role
            ) = \unserialize($serialized);
    }
}
