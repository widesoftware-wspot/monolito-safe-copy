<?php

namespace Wideti\DomainBundle\Builders;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Document\Guest\Social;

class GuestBuilder
{
    /**
     * @var Guest
     */
    private $guest;

    public function __construct()
    {
        $this->guest = new Guest();
    }

    public function build()
    {
        return $this->guest;
    }

    public function withId($id)
    {
        $this->guest->setId($id);
        return $this;
    }

    public function withMysql($mysql)
    {
        $this->guest->setMysql($mysql);
        return $this;
    }

    public function withEmail($email)
    {
        $this->guest->setEmail($email);
        return $this;
    }

    public function withPassword($password)
    {
        $this->guest->setPassword($password);
        return $this;
    }

    public function withStatus($status)
    {
        $this->guest->setStatus($status);
        return $this;
    }

    public function withIsEmailIsValid($emailIsValid)
    {
        $this->guest->setEmailIsValid($emailIsValid);
        return $this;
    }

    public function withEmailIsValidDate($isValidDate)
    {
        $this->guest->setEmailIsValidDate($isValidDate);
        return $this;
    }

    public function withLocale($locate)
    {
        $this->guest->setLocale($locate);
        return $this;
    }

    public function withDocumentType($documentType)
    {
        $this->guest->setDocumentType($documentType);
        return $this;
    }

    public function withAuthorizeEmail($authorizeEmail)
    {
        $this->guest->setAuthorizeEmail($authorizeEmail);
        return $this;
    }

    public function withRegistrationMacAddress($registrationMacAddress)
    {
        $this->guest->setRegistrationMacAddress($registrationMacAddress);
        return $this;
    }

    public function withReturning($returning)
    {
        $this->guest->setReturning($returning);
        return $this;
    }

    public function withProperties($properties)
    {
        $this->guest->setProperties($properties);
        return $this;
    }

    public function withSocial(Social $social)
    {
        $this->guest->addSocial($social);
        return $this;
    }

    public function withGroup($group)
    {
        $this->guest->setGroup($group);
        return $this;
    }
}
