<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="sms_billing_control")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\SmsBillingControlRepository")
 * @UniqueEntity(
 *     fields = { "client", "closingDateStart", "closingDateEnd" }
 * )
 */
class SMSBillingControl
{
    const STATUS_PENDING = 0;
    const STATUS_BILLED  = 1;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Client", cascade={"persist"})
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     */
    private $client;

    /**
     * @ORM\Column(name="closing_date_start", type="string", length=10, nullable=false)
     */
    private $closingDateStart;

    /**
     * @ORM\Column(name="closing_date_end", type="string", length=10, nullable=true)
     */
    private $closingDateEnd;

    /**
     * @ORM\Column(name="cost_per_sms", type="string", length=10)
     */
    private $costPerSms;

    /**
     * @ORM\Column(name="sent_sms_number", type="integer")
     */
    private $sentSmsNumber;

    /**
     * @ORM\Column(name="amount_to_pay", type="string", length=10)
     */
    private $amountToPay;

    /**
     * @ORM\Column(name="status", type="integer", options={"default":0} )
     */
    private $status;

    /**
     * @ORM\Column(name="registered_in", type="string", length=10, nullable=false)
     */
    private $registeredIn;

    /**
     * @ORM\Column(name="closing_date_reference", type="string", length=10, nullable=false)
     */
    private $closingDateReference;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return SMSBillingControl
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
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
     * @return SMSBillingControl
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getClosingDateStart()
    {
        return $this->closingDateStart;
    }

    /**
     * @param mixed $closingDateStart
     * @return SMSBillingControl
     */
    public function setClosingDateStart($closingDateStart)
    {
        $this->closingDateStart = $closingDateStart;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getClosingDateEnd()
    {
        return $this->closingDateEnd;
    }

    /**
     * @param mixed $closingDateEnd
     * @return SMSBillingControl
     */
    public function setClosingDateEnd($closingDateEnd)
    {
        $this->closingDateEnd = $closingDateEnd;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCostPerSms()
    {
        return $this->costPerSms;
    }

    /**
     * @param mixed $costPerSms
     * @return SMSBillingControl
     */
    public function setCostPerSms($costPerSms)
    {
        $this->costPerSms = $costPerSms;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSentSmsNumber()
    {
        return $this->sentSmsNumber;
    }

    /**
     * @param mixed $sentSmsNumber
     * @return SMSBillingControl
     */
    public function setSentSmsNumber($sentSmsNumber)
    {
        $this->sentSmsNumber = $sentSmsNumber;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAmountToPay()
    {
        return $this->amountToPay;
    }

    /**
     * @param mixed $amountToPay
     * @return SMSBillingControl
     */
    public function setAmountToPay($amountToPay)
    {
        $this->amountToPay = $amountToPay;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     * @return SMSBillingControl
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRegisteredIn()
    {
        return $this->registeredIn;
    }

    /**
     * @param $registeredIn
     * @return $this
     */
    public function setRegisteredIn($registeredIn)
    {
        $this->registeredIn = $registeredIn;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getClosingDateReference()
    {
        return $this->closingDateReference;
    }

    /**
     * @param mixed $closingDateReference
     * @return $this
     */
    public function setClosingDateReference($closingDateReference)
    {
        $this->closingDateReference = $closingDateReference;
        return $this;
    }
}