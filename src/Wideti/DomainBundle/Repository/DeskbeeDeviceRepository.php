<?php


namespace Wideti\DomainBundle\Repository;


use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\DeskbeeDevice;


class DeskbeeDeviceRepository extends EntityRepository
{
    public function save(DeskbeeDevice $deskbeeDevice)
    {
        $this->_em->merge($deskbeeDevice);
        $this->_em->flush();
        return $deskbeeDevice;
    }

    /**
     * @param AccessPoints $accessPoint
     * @return DeskbeeDevice
     */
    public function getDeskbeeDeviceByAccessPoint(AccessPoints $accessPoint)
    {
        return $this->findOneBy(['accessPoint' => $accessPoint]);
    }

    /**
     * @param AccessPoints $accessPoint
     * @return DeskbeeDevice
     */
    public function setAccessPoint(AccessPoints $accessPoint)
    {
        $deskbeeDevice = $accessPoint->getDeskbeeDevice();
        if ($deskbeeDevice) {
            $deskbeeDevice->setAccessPoint($accessPoint);
        }

        $this->_em->persist($deskbeeDevice);
        $this->_em->flush();
    }

    /**
     * @param AccessPoints $accessPoint
     * @param string $device
     */
    public function getOrCreateDeskbeeDevice($accessPoint) {
        $deskbeeDevice = $this->getDeskbeeDeviceByAccessPoint($accessPoint);
        if (!$deskbeeDevice) {
            $deskbeeDevice = new DeskbeeDevice();
            $deskbeeDevice->setAccessPoint($accessPoint);
            $deskbeeDevice->setDevice('');
            $deskbeeDevice = $this->save($deskbeeDevice);
        }
        return $deskbeeDevice;
    }

    /**
     * @param DeskbeeDevice $deskbeeDevice
     * @return void
     */
    public function deleteDevice(DeskbeeDevice $deskbeeDevice)
    {
        $this->_em->remove($deskbeeDevice);
        $this->_em->flush();
    }

}