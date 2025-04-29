<?php


namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Exclude;

/**
 * @ORM\Table(name="campaign_views_aggregated")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\CampaignViewsAggregatedRepository")
 */
class CampaignViewsAggregated
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="client_id", type="integer")
     */
    private $client;

    /**
     * @ORM\Column(name="campaign_id", type="integer")
     */
    private $campaign;

    /**
     * @ORM\Column(name="step", type="integer")
     */
    private $step;

    /**
     * @ORM\Column(name="last_aggregation_time", type="datetime")
     * @var \DateTime
     */
    private $lastAggregationTime;

    /**
     * @ORM\Column(name="total", type="integer")
     */
    private $total;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     * @return $this
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
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return mixed
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * @param mixed $campaign
     */
    public function setCampaign($campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * @return mixed
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * @param mixed $step
     */
    public function setStep($step)
    {
        $this->step = $step;
    }

    /**
     * @return \DateTime
     */
    public function getLastAggregationTime()
    {
        return $this->lastAggregationTime;
    }

    /**
     * @param \DateTime $lastAggregationTime
     */
    public function setLastAggregationTime($lastAggregationTime)
    {
        $this->lastAggregationTime = $lastAggregationTime;
    }

    /**
     * @return mixed
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param mixed $total
     */
    public function setTotal($total)
    {
        $this->total = $total;
    }
}
