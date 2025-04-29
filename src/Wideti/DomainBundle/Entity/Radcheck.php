<?php

namespace Wideti\DomainBundle\Entity;

use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use Wideti\DomainBundle\Helpers\DateTimeHelper;
use Wideti\DomainBundle\Service\Timezone\TimezoneService;

/**
 * @ORM\Table(name="radcheck", indexes={@ORM\Index(name="username", columns={"username"})})
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\RadcheckRepository")
 */
class Radcheck
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Guests", inversedBy="radcheck", cascade={"persist"})
     * @ORM\JoinColumn(name="username", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $guest;

    /**
     * @ORM\Column(name="attribute", type="string", length=64, nullable=false)
     */
    private $attribute;

    /**
     * @ORM\Column(name="op", type="string", length=2, nullable=false)
     */
    private $op;

    /**
     * @ORM\Column(name="value", type="string", length=253, nullable=false)
     */
    private $value;

    /**
     * @ORM\Column(name="ap_timezone", type="string", length=253, nullable=false)
     */
    private $apTimezone;

    /**
     * @ORM\Column(name="group_id", type="string", length=100, nullable=true)
     */
    private $groupId;

    /**
     * @Type("integer")
     * @Accessor(getter="getClientSerialize")
     * @SerializedName("client_id")
     * @ORM\ManyToOne(targetEntity="Client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     *
     */
    protected $client;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getAttribute()
    {
        return $this->attribute;
    }

    public function setAttribute($attribute)
    {
        $this->attribute = $attribute;
    }

    public function getOp()
    {
        return $this->op;
    }

    public function setOp($op)
    {
        $this->op = $op;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
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
    public function getApTimezone()
    {
        return $this->apTimezone;
    }

    /**
     * @param mixed $apTimezone
     */
    public function setApTimezone($apTimezone)
    {
        $this->apTimezone = $apTimezone;
    }

    /**
     * @return mixed
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @param mixed $gropId
     */
    public function setGroupId($gropId)
    {
        $this->groupId = $gropId;
    }

    public function getFormatted()
    {
        $expirationTime = DateTimeHelper::formatDate($this->getValue());

        $expirationTimeUTC = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $expirationTime,
            new \DateTimeZone(TimezoneService::UTC)
        );

        return $expirationTimeUTC;
    }
}
