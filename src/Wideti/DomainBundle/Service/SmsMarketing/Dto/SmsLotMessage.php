<?php

namespace Wideti\DomainBundle\Service\SmsMarketing\Dto;

class SmsLotMessage
{
    private $id;
    private $lotNumber;
    private $clientId;
    private $guestId;
    private $mobileNumber;
    private $message;

    /**
     * SmsLotMessage constructor.
     * @param $id
     * @param $lotNumber
     * @param $clientId
     * @param $guestId
     * @param $mobileNumber
     * @param $message
     */
    public function __construct($id, $lotNumber, $clientId, $guestId, $mobileNumber, $message)
    {
        $this->id = $id;
        $this->lotNumber = $lotNumber;
        $this->clientId = $clientId;
        $this->guestId = $guestId;
        $this->mobileNumber = $mobileNumber;
        $this->message = $message;
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
    public function getLotNumber()
    {
        return $this->lotNumber;
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
    public function getGuestId()
    {
        return $this->guestId;
    }

    /**
     * @return mixed
     */
    public function getMobileNumber()
    {
        return $this->mobileNumber;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }
}
