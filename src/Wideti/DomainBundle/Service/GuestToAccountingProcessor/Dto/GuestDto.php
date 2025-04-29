<?php

namespace Wideti\DomainBundle\Service\GuestToAccountingProcessor\Dto;

class GuestDto implements \JsonSerializable
{
    /**
     * @var integer
     */
    private $clientId;
    /**
     * @var integer
     */
    private $id;
    /**
     * @var string
     */
    private $mongoId;
    /**
     * @var string
     */
    private $group;
    /**
     * @var integer
     */
    private $status;
    /**
     * @var boolean
     */
    private $emailIsValid;
    /**
     * @var string
     */
    private $emailIsValidDate;
    /**
     * @var string
     */
    private $registerMode;
    /**
     * @var string
     */
    private $locale;
    /**
     * @var string
     */
    private $documentType;
    /**
     * @var string
     */
    private $registrationMacAddress;
    /**
     * @var string
     */
    private $created;
    /**
     * @var string
     */
    private $lastAccess;
    /**
     * @var string
     */
    private $timezone;
    /**
     * @var array
     */
    private $accessData;
    /**
     * @var array
     */
    private $social;
    /**
     * @var array
     */
    private $properties;
    /**
     * @var string
     */
    private $loginField;

    /**
     * GuestDto constructor.
     * @param $clientId
     * @param $id
     * @param $mongoId
     * @param $group
     * @param $status
     * @param $emailIsValid
     * @param $emailIsValidDate
     * @param $registerMode
     * @param $locale
     * @param $documentType
     * @param $registrationMacAddress
     * @param $created
     * @param $lastAccess
     * @param $timezone
     * @param $accessData
     * @param $social
     * @param $properties
     * @param $loginField
     */
    public function __construct(
        $clientId,
        $id,
        $mongoId,
        $group,
        $status,
        $emailIsValid,
        $emailIsValidDate,
        $registerMode,
        $locale,
        $documentType,
        $registrationMacAddress,
        $created,
        $lastAccess,
        $timezone,
        $accessData,
        $social,
        $properties,
        $loginField
    ) {
        $this->clientId = $clientId;
        $this->id = $id;
        $this->mongoId = $mongoId;
        $this->group = $group;
        $this->status = $status;
        $this->emailIsValid = $emailIsValid;
        $this->emailIsValidDate = $emailIsValidDate;
        $this->registerMode = $registerMode;
        $this->locale = $locale;
        $this->documentType = $documentType;
        $this->registrationMacAddress = $registrationMacAddress;
        $this->created = $created;
        $this->lastAccess = $lastAccess;
        $this->timezone = $timezone;
        $this->accessData = $accessData;
        $this->social = $social;
        $this->properties = $properties;
        $this->loginField = $loginField;
    }

    /**
     * @return mixed
     */
    public function getClientId()
    {
        return $this->clientId;
    }

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
    public function getMongoId()
    {
        return $this->mongoId;
    }

    /**
     * @return mixed
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getEmailIsValid()
    {
        return $this->emailIsValid;
    }

    /**
     * @return mixed
     */
    public function getEmailIsValidDate()
    {
        return $this->emailIsValidDate;
    }

    /**
     * @return mixed
     */
    public function getRegisterMode()
    {
        return $this->registerMode;
    }

    /**
     * @return mixed
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return mixed
     */
    public function getDocumentType()
    {
        return $this->documentType;
    }

    /**
     * @return mixed
     */
    public function getRegistrationMacAddress()
    {
        return $this->registrationMacAddress;
    }

    /**
     * @return mixed
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return mixed
     */
    public function getLastAccess()
    {
        return $this->lastAccess;
    }

    /**
     * @return mixed
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @return mixed
     */
    public function getAccessData()
    {
        return $this->accessData;
    }

    /**
     * @return mixed
     */
    public function getSocial()
    {
        return $this->social;
    }

    /**
     * @return mixed
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return string
     */
    public function getLoginField()
    {
        return $this->loginField;
    }

    public function getAsArray()
    {
        return [
            'clientId'                  => $this->getClientId(),
            'id'                        => $this->getId(),
            'mongoId'                   => $this->getMongoId(),
            'group'                     => $this->getGroup(),
            'status'                    => $this->getStatus(),
            'emailIsValid'              => $this->getEmailIsValid(),
            'emailIsValidDate'          => $this->getEmailIsValidDate(),
            'registerMode'              => $this->getRegisterMode(),
            'locale'                    => $this->getLocale(),
            'documentType'              => $this->getDocumentType(),
            'registrationMacAddress'    => $this->getRegistrationMacAddress(),
            'created'                   => $this->getCreated(),
            'lastAccess'                => $this->getLastAccess(),
            'timezone'                  => $this->getTimezone(),
            'accessData'                => $this->getAccessData(),
            'social'                    => $this->getSocial(),
            'properties'                => $this->getProperties(),
            'loginField'                => $this->getLoginField()
        ];
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'clientId'                  => $this->clientId,
            'id'                        => $this->id,
            'mongoId'                   => $this->mongoId,
            'group'                     => $this->group,
            'status'                    => $this->status,
            'emailIsValid'              => $this->emailIsValid,
            'emailIsValidDate'          => $this->emailIsValidDate,
            'registerMode'              => $this->registerMode,
            'locale'                    => $this->locale,
            'documentType'              => $this->documentType,
            'registrationMacAddress'    => $this->registrationMacAddress,
            'created'                   => $this->created,
            'lastAccess'                => $this->lastAccess,
            'timezone'                  => $this->timezone,
            'accessData'                => $this->accessData,
            'social'                    => $this->social,
            'properties'                => $this->properties,
            'loginField'                => $this->loginField
        ];
    }
}
