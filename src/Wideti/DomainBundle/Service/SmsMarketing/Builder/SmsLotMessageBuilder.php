<?php

namespace Wideti\DomainBundle\Service\SmsMarketing\Builder;

use Wideti\DomainBundle\Service\SmsMarketing\Dto\SmsLotMessage;

class SmsLotMessageBuilder
{
    private $id;
    private $lotNumber;
    private $clientId;
    private $guestId;
    private $mobileNumber;
    private $message;

    public static function getBuilder()
    {
        return new SmsLotMessageBuilder();
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
     * @param $lotNumber
     * @return $this
     */
    public function withLotNumber($lotNumber)
    {
        $this->lotNumber = $lotNumber;
        return $this;
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
     * @param $guestId
     * @return $this
     */
    public function withGuestId($guestId)
    {
        $this->guestId = $guestId;
        return $this;
    }

    /**
     * @param $mobileNumber
     * @return $this
     */
    public function withMobileNumber($mobileNumber)
    {
        $this->mobileNumber = $mobileNumber;
        return $this;
    }

    /**
     * @param $message
     * @return $this
     */
    public function withMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    public function build()
    {
        return new SmsLotMessage(
            $this->id,
            $this->lotNumber,
            $this->clientId,
            $this->guestId,
            $this->mobileNumber,
            $this->message
        );
    }
}
