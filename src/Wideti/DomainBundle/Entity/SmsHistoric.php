<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="sms_historic")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\SmsHistoricRepository")
 * @ORM\HasLifecycleCallbacks
 */
class SmsHistoric
{
    const SENDER_TWILIO = "TWILIO";
    const SENDER_WAVY   = "WAVY";

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Client", cascade={"persist"})
     * @ORM\Column(name="client_id", type="integer", nullable=true)
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity="Guests", inversedBy="acessos")
     * @ORM\JoinColumn(name="guest_id", referencedColumnName="id", nullable=false)
     */
    protected $guest;

    /**
     * @ORM\Column(name="message_status_code", type="string", length=45, nullable=true)
     */
    private $messageStatusCode;

    /**
     * @ORM\Column(name="message_status", type="string", length=100, nullable=true)
     */
    private $messageStatus;

    /**
     * @ORM\Column(name="message_id", type="string", length=100, nullable=true)
     */
    private $message_id;

    /**
     * @ORM\Column(name="body_message", type="string", length=200, nullable=true)
     */
    private $bodyMessage;

    /**
     * @ORM\Column(name="sms_cost", type="string", length=10)
     */
    protected $smsCost;

    /**
     * @ORM\Column(name="access_point", type="string", length=100, nullable=true)
     */
    private $accessPoint;

    /**
     * @ORM\Column(name="sent_to", type="string", length=100, nullable=true)
     */
    private $sentTo;

    /**
     * @ORM\Column(name="sent_date", type="datetime", nullable=true)
     */
    private $sentDate;

    /**
     * @ORM\Column(name="sender", type="string", length=100, nullable=true)
     */
    private $sender;

    /**
     * @ORM\Column(name="carrier", type="string", length=100, nullable=true)
     */
    private $carrier;

    /**
     * @ORM\Column(name="delivered_status", type="string", length=100, nullable=true)
     */
    private $deliveredStatus;

    /**
     * @ORM\Column(name="delivered_date", type="datetime", nullable=true)
     */
    private $deliveredDate;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set messageStatus
     *
     * @param  string      $messageStatus
     * @return SmsHistoric
     */
    public function setMessageStatus($messageStatus)
    {
        $this->messageStatus = $messageStatus;

        return $this;
    }

    /**
     * Get messageStatus
     *
     * @return string
     */
    public function getMessageStatus()
    {
        return $this->messageStatus;
    }

    /**
     * Set message_id
     *
     * @param  string      $messageId
     * @return SmsHistoric
     */
    public function setMessageId($messageId)
    {
        $this->message_id = $messageId;

        return $this;
    }

    /**
     * Get message_id
     *
     * @return string
     */
    public function getMessageId()
    {
        return $this->message_id;
    }

    /**
     * Set sentTo
     *
     * @param  string      $sentTo
     * @return SmsHistoric
     */
    public function setSentTo($sentTo)
    {
        $this->sentTo = $sentTo;

        return $this;
    }

    /**
     * Get sentTo
     *
     * @return string
     */
    public function getSentTo()
    {
        return $this->sentTo;
    }

    /**
     * Set sentDate
     *
     * @param  \DateTime   $sentDate
     * @return SmsHistoric
     */
    public function setSentDate($sentDate)
    {
        $this->sentDate = $sentDate;

        return $this;
    }

    /**
     * Get sentDate
     *
     * @return \DateTime
     */
    public function getSentDate()
    {
        return $this->sentDate;
    }

    /**
     * @param mixed $bodyMessage
     */
    public function setBodyMessage($bodyMessage)
    {
        $this->bodyMessage = $bodyMessage;
    }

    /**
     * @return mixed
     */
    public function getBodyMessage()
    {
        return $this->bodyMessage;
    }

    /**
     * @return mixed
     */
    public function getSmsCost()
    {
        return $this->smsCost;
    }

    /**
     * @param mixed $smsCost
     */
    public function setSmsCost($smsCost)
    {
        $this->smsCost = $smsCost;
    }

    /**
     * @return mixed
     */
    public function getAccessPoint()
    {
        return $this->accessPoint;
    }

    /**
     * @param mixed $accessPoint
     */
    public function setAccessPoint($accessPoint)
    {
        $this->accessPoint = $accessPoint;
    }

    /**
     *  @ORM\PrePersist
     */
    public function setSentDateValue()
    {
        $this->setSentDate(new \DateTime());
    }

    public function setGuest(\Wideti\DomainBundle\Entity\Guests $guest = null)
    {
        $this->guest = $guest;

        return $this;
    }

    public function getGuest()
    {
        return $this->guest;
    }

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param mixed $client
     * @return Client
     */
    public function setClient($client)
    {
        $this->client = $client;

        return $this;
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
    public function getCarrier()
    {
        return $this->carrier;
    }

    /**
     * @param mixed $carrier
     */
    public function setCarrier($carrier)
    {
        $this->carrier = $carrier;
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
     * @return mixed
     */
    public function getMessageStatusCode()
    {
        return $this->messageStatusCode;
    }

    /**
     * @param mixed $messageStatusCode
     */
    public function setMessageStatusCode($messageStatusCode)
    {
        $this->messageStatusCode = $messageStatusCode;
    }
}
