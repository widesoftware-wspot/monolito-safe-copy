<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="access_code_control",
 *
 * )
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\AccessCodeControlRepository")
 */
class AccessCodeControl
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="client_id", type="integer", nullable=true)
     */
    private $clientId;

    /**
     * @ORM\Column(name="guest_id", type="integer")
     */
    private $guestId;

    /**
     * @ORM\Column(name="has_to_use_access_code", type="boolean")
     */
    private $hasToUseAccessCode;

    /**
     * @ORM\Column(name="already_used_access_code", type="boolean")
     */
    private $alreadyUsedAccessCode;

    /**
     * @param $clientId
     * @param $guestId
     * @param $hasToUseAccessCode
     * @param $alreadyUsedAccessCode
     */
    public function __construct($clientId, $guestId, $hasToUseAccessCode, $alreadyUsedAccessCode)
    {
        $this->clientId = $clientId;
        $this->guestId = $guestId;
        $this->hasToUseAccessCode = $hasToUseAccessCode;
        $this->alreadyUsedAccessCode = $alreadyUsedAccessCode;
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
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param mixed $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @return mixed
     */
    public function getGuestId()
    {
        return $this->guestId;
    }

    /**
     * @param mixed $guestId
     */
    public function setGuestId($guestId)
    {
        $this->guestId = $guestId;
    }

    /**
     * @return mixed
     */
    public function getHasToUseAccessCode()
    {
        return $this->hasToUseAccessCode;
    }

    /**
     * @param mixed $hasToUseAccessCode
     */
    public function setHasToUseAccessCode($hasToUseAccessCode)
    {
        $this->hasToUseAccessCode = $hasToUseAccessCode;
    }

    /**
     * @return mixed
     */
    public function getAlreadyUsedAccessCode()
    {
        return $this->alreadyUsedAccessCode;
    }

    /**
     * @param mixed $alreadyUsedAccessCode
     */
    public function setAlreadyUsedAccessCode($alreadyUsedAccessCode)
    {
        $this->alreadyUsedAccessCode = $alreadyUsedAccessCode;
    }


}