<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Device;
use Wideti\DomainBundle\Entity\DeviceEntry;

class DeviceRepository extends EntityRepository
{
    public function create(Device $device)
    {
        $em = $this->getEntityManager();
        $em->persist($device);
        $em->flush();
        return $device;
    }

    public function createDeviceAndEntryByTheSameTransaction(
        Device $device,
        Guest $guest,
        Client $client,
        AccessPoints $accessPoint = null
    ) {
        $em = $this->getEntityManager();
        $guestMysql = $em->getRepository("DomainBundle:Guests")->findOneBy(['id' => $guest->getMysql()]);

        $em->getConnection()->beginTransaction();
        try {
            $em->persist($device);


            if (is_null($accessPoint)) {
                $entry = new DeviceEntry(
                    $device,
                    $guestMysql,
                    $client,
                    null,
                    null,
                    null
                );
            } else {
                $entry = new DeviceEntry(
                    $device,
                    $guestMysql,
                    $client,
                    $accessPoint->getIdentifier(),
                    $accessPoint->getFriendlyName(),
                    $accessPoint->getTimezone()
                );
            }



            $em->persist($entry);
            $em->flush();

            $em->getConnection()->commit();
        } catch (\Exception $exception) {
            $em->getConnection()->rollBack();
            throw $exception;
        }
    }
}
