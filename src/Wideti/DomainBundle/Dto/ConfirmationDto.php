<?php

namespace Wideti\DomainBundle\Dto;

class ConfirmationDto
{
    const EMAIL = "confirmation_email";
    const SMS   = "confirmation_sms";

    private $isConfirmationNeeded = false;
    private $confirmationType = "";

    /**
     * @return boolean
     */
    public function isConfirmationNeeded()
    {
        return $this->isConfirmationNeeded;
    }

    /**
     * @param boolean $isConfirmationNeeded
     */
    public function setIsConfirmationNeeded($isConfirmationNeeded)
    {
        $this->isConfirmationNeeded = $isConfirmationNeeded;
    }

    /**
     * @return mixed
     */
    public function getConfirmationType()
    {
        return $this->confirmationType;
    }

    /**
     * @param mixed $confirmationType
     */
    public function setConfirmationType($confirmationType)
    {
        $this->confirmationType = $confirmationType;
    }


}