<?php

namespace Wideti\DomainBundle\Dto;

use Wideti\DomainBundle\Document\Guest\Guest;

class SignInStatusDto
{
    const GUEST_NON_EXISTS                   = 'guest_non_exists';
    const EMAIL_IS_INVALID                   = 'email_is_invalid';
    const SIGNIN_WITH_CONFIRMATION           = 'signin_with_confirmation';
    const SIGNIN_WITH_CONFIRMATION_BLOCKED   = 'signin_with_confirmation_blocked';
    const SIGNIN_SUCCESS                     = 'signin_success';

    private $status;
    /**
     * @var Guest
     */
    private $guest;

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
     * @return Guest
     */
    public function getGuest()
    {
        return $this->guest;
    }

    /**
     * @param Guest $guest
     */
    public function setGuest(Guest $guest)
    {
        $this->guest = $guest;
    }
}
