<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="api_wspot_roles")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\ApiWSpotRolesRepository")
 */
class ApiWSpotRoles
{
    const ROLE_API   = "ROLE_API";
    const ROLE_READ  = 0;
    const ROLE_WRITE = 1;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="ApiWSpot", inversedBy="roles")
     * @ORM\JoinColumn(name="api_wspot_id", referencedColumnName="id")
     * @Exclude()
     */
    protected $api;

	/**
	 * @ORM\Column(name="role", type="string", length=55)
	 */
	private $role;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * @param mixed $api
     */
    public function setApi($api)
    {
        $this->api = $api;
    }

    /**
     * @return mixed
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param mixed $role
     */
    public function setRole($role)
    {
        $this->role = $role;
    }
}
