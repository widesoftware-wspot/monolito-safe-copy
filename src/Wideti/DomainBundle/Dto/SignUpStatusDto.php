<?php

namespace Wideti\DomainBundle\Dto;

use Wideti\DomainBundle\Document\Guest\Guest;

class SignUpStatusDto
{
    /**
     * @var Guest
     */
    private $createdGuest;

    /**
     * @var ConfirmationDto
     */
    private $confirmation;

    /**
     * @return Guest
     */
    public function getCreatedGuest()
    {
        return $this->createdGuest;
    }

    /**
     * @param Guest $createdGuest
     */
    public function setCreatedGuest(Guest $createdGuest)
    {
        $this->createdGuest = $createdGuest;
    }

    /**
     * @return ConfirmationDto
     */
    public function getConfirmation()
    {
        return $this->confirmation;
    }

    /**
     * @param ConfirmationDto $confirmation
     */
    public function setConfirmation(ConfirmationDto $confirmation)
    {
        $this->confirmation = $confirmation;
    }
}
