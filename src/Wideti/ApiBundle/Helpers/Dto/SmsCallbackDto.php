<?php

namespace Wideti\ApiBundle\Helpers\Dto;

class SmsCallbackDto implements \JsonSerializable
{
    const SENDER_WAVY = "WAVY";

    public $sender;
    public $id;
    public $carrierName;
    public $destination;
    public $sentStatusCode;
    public $sentStatus;
    public $deliveredStatus;
    public $deliveredDate;

    /**
     * SmsCallbackDto constructor.
     * @param $sender
     * @param $id
     * @param $carrierName
     * @param $destination
     * @param $sentStatusCode
     * @param $sentStatus
     * @param $deliveredStatus
     * @param $deliveredDate
     */
    public function __construct(
        $sender,
        $id,
        $carrierName,
        $destination,
        $sentStatusCode,
        $sentStatus,
        $deliveredStatus,
        $deliveredDate
    ) {
        $this->sender = $sender;
        $this->id = $id;
        $this->carrierName = $carrierName;
        $this->destination = $destination;
        $this->sentStatusCode = $sentStatusCode;
        $this->sentStatus = $sentStatus;
        $this->deliveredStatus = $deliveredStatus;
        $this->deliveredDate = $deliveredDate;
    }

    /**
     * @return mixed
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @param mixed $sender
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
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
    public function getCarrierName()
    {
        return $this->carrierName;
    }

    /**
     * @param mixed $carrierName
     */
    public function setCarrierName($carrierName)
    {
        $this->carrierName = $carrierName;
    }

    /**
     * @return mixed
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * @param mixed $destination
     */
    public function setDestination($destination)
    {
        $this->destination = $destination;
    }

    /**
     * @return mixed
     */
    public function getSentStatusCode()
    {
        return $this->sentStatusCode;
    }

    /**
     * @param mixed $sentStatusCode
     */
    public function setSentStatusCode($sentStatusCode)
    {
        $this->sentStatusCode = $sentStatusCode;
    }

    /**
     * @return mixed
     */
    public function getSentStatus()
    {
        return $this->sentStatus;
    }

    /**
     * @param mixed $sentStatus
     */
    public function setSentStatus($sentStatus)
    {
        $this->sentStatus = $sentStatus;
    }

    /**
     * @return mixed
     */
    public function getDeliveredStatus()
    {
        return $this->deliveredStatus;
    }

    /**
     * @param mixed $deliveredStatus
     */
    public function setDeliveredStatus($deliveredStatus)
    {
        $this->deliveredStatus = $deliveredStatus;
    }

    /**
     * @return mixed
     */
    public function getDeliveredDate()
    {
        return $this->deliveredDate;
    }

    /**
     * @param mixed $deliveredDate
     */
    public function setDeliveredDate($deliveredDate)
    {
        $this->deliveredDate = $deliveredDate;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource.
     *
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'sender'            => $this->sender,
            'messageId'         => $this->id,
            'carrier'           => $this->carrierName,
            'destination'       => $this->destination,
            'sentStatusCode'    => $this->sentStatusCode,
            'sentStatus'        => $this->sentStatus,
            'deliveredStatus'   => $this->deliveredStatus,
            'deliveredDate'     => $this->deliveredDate
        ];
    }
}
