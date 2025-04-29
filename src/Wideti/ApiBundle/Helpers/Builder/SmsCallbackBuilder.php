<?php

namespace Wideti\ApiBundle\Helpers\Builder;

use Wideti\ApiBundle\Helpers\Dto\SmsCallbackDto;

class SmsCallbackBuilder
{
    public $sender;
    public $id;
    public $carrierName;
    public $destination;
    public $sentStatusCode;
    public $sentStatus;
    public $deliveredStatus;
    public $deliveredDate;

    /**
     * @return SmsCallbackBuilder
     */
    public static function getBuilder()
    {
        return new SmsCallbackBuilder();
    }

    /**
     * @param $sender
     * @return mixed
     */
    public function withSender($sender)
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function withId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param $carrierName
     * @return mixed
     */
    public function withCarrierName($carrierName)
    {
        $this->carrierName = $carrierName;
        return $this;
    }

    /**
     * @param $destination
     * @return mixed
     */
    public function withDestination($destination)
    {
        $this->destination = $destination;
        return $this;
    }

    /**
     * @param $sentStatusCode
     * @return mixed
     */
    public function withSentStatusCode($sentStatusCode)
    {
        $this->sentStatusCode = $sentStatusCode;
        return $this;
    }

    /**
     * @param $sentStatus
     * @return mixed
     */
    public function withSentStatus($sentStatus)
    {
        $this->sentStatus = $sentStatus;
        return $this;
    }

    /**
     * @param $deliveredStatus
     * @return mixed
     */
    public function withDeliveredStatus($deliveredStatus)
    {
        $this->deliveredStatus = $deliveredStatus;
        return $this;
    }

    /**
     * @param $deliveredDate
     * @return mixed
     */
    public function withDeliveredDate($deliveredDate)
    {
        $this->deliveredDate = $deliveredDate;
        return $this;
    }

    /**
     * @return SmsCallbackDto
     */
    public function build()
    {
        return new SmsCallbackDto(
            $this->sender,
            $this->id,
            $this->carrierName,
            $this->destination,
            $this->sentStatusCode,
            $this->sentStatus,
            $this->deliveredStatus,
            $this->deliveredDate
        );
    }
}
