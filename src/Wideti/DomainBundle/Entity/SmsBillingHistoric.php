<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="sms_billing_historic")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\SmsBillingHistoricRepository")
 */
class SmsBillingHistoric
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Client", cascade={"persist"})
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     *
     */
    protected $client;

    /**
     * @ORM\Column(name="total_sent", type="integer")
     */
    private $totalSent;

    /**
     * @ORM\Column(name="total_cost", type="string", length=10)
     */
    private $totalCost;

    /**
     * @ORM\Column(name="date", type="datetime", nullable=true)
     */
    private $date;

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

    public function setClient(\Wideti\DomainBundle\Entity\Client $client)
    {
        $this->client = $client;

        return $this;
    }

    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return mixed
     */
    public function getTotalSent()
    {
        return $this->totalSent;
    }

    /**
     * @param mixed $totalSent
     */
    public function setTotalSent($totalSent)
    {
        $this->totalSent = $totalSent;
    }

    /**
     * @return mixed
     */
    public function getTotalCost()
    {
        return $this->totalCost;
    }

    /**
     * @param mixed $totalCost
     */
    public function setTotalCost($totalCost)
    {
        $this->totalCost = $totalCost;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param mixed $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }
}
