<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="access_code_codes")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\AccessCodeCodesRepository")
 */
class AccessCodeCodes
{
    const USED = true;

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="AccessCode", inversedBy="codes", cascade={"persist"})
     * @ORM\JoinColumn(name="access_code_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $accessCode;

    /**
     * @ORM\Column(name="code", type="string", length=50)
     */
    protected $code;

    /**
     * @ORM\Column(name="used", type="boolean", options={"default":0} )
     */
    private $used = 0;

    /**
     * @ORM\Column(name="used_time", type="datetime", nullable=true)
     */
    private $usedTime;

    /**
     * @ORM\ManyToOne(targetEntity="Guests", inversedBy="codes", cascade={"persist"})
     * @ORM\JoinColumn(name="guest", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $guest;

    public function __toString()
    {
        return $this->getCode();
    }

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
    public function getAccessCode()
    {
        return $this->accessCode;
    }

    /**
     * @param mixed $accessCode
     */
    public function setAccessCode($accessCode)
    {
        $this->accessCode = $accessCode;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param mixed $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return mixed
     */
    public function getUsed()
    {
        return $this->used;
    }

    /**
     * @param mixed $used
     */
    public function setUsed($used)
    {
        $this->used = $used;
    }

    /**
     * @return mixed
     */
    public function getUsedTime()
    {
        return $this->usedTime;
    }

    /**
     * @param mixed $usedTime
     */
    public function setUsedTime($usedTime)
    {
        $this->usedTime = $usedTime;
    }

    /**
     * @return mixed
     */
    public function getGuest()
    {
        return $this->guest;
    }

    /**
     * @param Guests $guest
     * @return $this
     */
    public function setGuest(Guests $guest)
    {
        $this->guest = $guest;
        return $this;
    }
}
