<?php

namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="sms_credit")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\SmsCreditRepository")
 */
class SmsCredit
{
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
     * @ORM\Column(name="total_available", type="integer", nullable=true)
     */
    private $totalAvailable;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    public $updated;

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
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param $client
     * @return $this
     */
    public function setClient($client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTotalAvailable()
    {
        return $this->totalAvailable;
    }

    /**
     * @param mixed $totalAvailable
     */
    public function setTotalAvailable($totalAvailable)
    {
        $this->totalAvailable = $totalAvailable;
    }

    /**
     * @return mixed
     */
    public function getTotalUsed()
    {
        return $this->totalUsed;
    }
}
