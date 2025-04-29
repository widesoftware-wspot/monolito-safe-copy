<?php
namespace Wideti\DomainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("deskbee_device")
 * @ORM\Entity(repositoryClass="Wideti\DomainBundle\Repository\DeskbeeDeviceRepository")
 */
class DeskbeeDevice
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="Wideti\DomainBundle\Entity\AccessPoints", inversedBy="deskbeeDevice")
     * @ORM\JoinColumn(name="access_point_id", referencedColumnName="id")
     */
    private $accessPoint;

    /**
     * @ORM\Column(name="device", type="string", length=40, nullable=true)
     */
    private $device;

    /**
     * @return mixed
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * @param $device
     * @return $this
     */
    public function setDevice($device)
    {
        $this->device = $device;
        return $this;
    }

    /**
     * @return AccessPoint
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

}