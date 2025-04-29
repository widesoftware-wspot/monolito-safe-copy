<?php

namespace Wideti\DomainBundle\Service\GuestToAccountingProcessor\Builder;

use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Dto\GuestDto;

class GuestBuilder
{
    private $clientId;
    private $id;
    private $mongoId;
    private $group;
    private $status;
    private $emailIsValid;
    private $emailIsValidDate;
    private $registerMode;
    private $locale;
    private $documentType;
    private $registrationMacAddress;
    private $created;
    private $lastAccess;
    private $timezone;
    private $accessData;
    private $social;
    private $properties;
    private $loginField;

    public static function getBuilder()
    {
        return new GuestBuilder();
    }

    /**
     * @param $clientId
     * @return $this
     */
    public function withClientId($clientId)
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * @param $id
     * @return $this
     */
    public function withId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param $mongoId
     * @return $this
     */
    public function withMongoId($mongoId)
    {
        $this->mongoId = $mongoId;
        return $this;
    }

    /**
     * @param $group
     * @return $this
     */
    public function withGroup($group)
    {
        $this->group = $group;
        return $this;
    }

    /**
     * @param $status
     * @return $this
     */
    public function withStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @param $emailIsValid
     * @return $this
     */
    public function withEmailIsValid($emailIsValid)
    {
        $this->emailIsValid = $emailIsValid;
        return $this;
    }

    /**
     * @param $emailIsValidDate
     * @return $this
     */
    public function withEmailIsValidDate($emailIsValidDate)
    {
        $this->emailIsValidDate = $emailIsValidDate;
        return $this;
    }

    /**
     * @param $registerMode
     * @return $this
     */
    public function withRegisterMode($registerMode)
    {
        $this->registerMode = $registerMode;
        return $this;
    }

    /**
     * @param $locale
     * @return $this
     */
    public function withLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * @param $documentType
     * @return $this
     */
    public function withDocumentType($documentType)
    {
        $this->documentType = $documentType;
        return $this;
    }

    /**
     * @param $registrationMacAddress
     * @return $this
     */
    public function withRegistrationMacAddress($registrationMacAddress)
    {
        $this->registrationMacAddress = $registrationMacAddress;
        return $this;
    }

    /**
     * @param $created
     * @return $this
     */
    public function withCreated($created)
    {
        $this->created = $created;
        return $this;
    }

    /**
     * @param $lastAccess
     * @return $this
     */
    public function withLastAccess($lastAccess)
    {
        $this->lastAccess = $lastAccess;
        return $this;
    }

    /**
     * @param $timezone
     * @return $this
     */
    public function withTimezone($timezone)
    {
        $this->timezone = $timezone;
        return $this;
    }

    /**
     * @param $accessData
     * @return $this
     */
    public function withAccessData($accessData)
    {
        $this->accessData = $accessData;
        return $this;
    }

    /**
     * @param $social
     * @return $this
     */
    public function withSocial($social)
    {
        $this->social = $social;
        return $this;
    }

    /**
     * @param $properties
     * @return $this
     */
    public function withProperties($properties)
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * @param $loginField
     * @return $this
     */
    public function withLoginField($loginField)
    {
        $this->loginField = $loginField;
        return $this;
    }

    public function build()
    {
        return new GuestDto(
            $this->clientId,
            $this->id,
            $this->mongoId,
            $this->group,
            $this->status,
            $this->emailIsValid,
            $this->emailIsValidDate,
            $this->registerMode,
            $this->locale,
            $this->documentType,
            $this->registrationMacAddress,
            $this->created,
            $this->lastAccess,
            $this->timezone,
            $this->accessData,
            $this->social,
            $this->properties,
            $this->loginField
        );
    }
}
