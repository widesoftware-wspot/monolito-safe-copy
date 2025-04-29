<?php

namespace Wideti\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Device;
use Wideti\DomainBundle\Entity\DeviceEntry;
use Wideti\DomainBundle\Entity\Guests;
use Wideti\DomainBundle\Service\Timezone\TimezoneService;

class DeviceEntryRepository extends EntityRepository
{
    const PERIOD_ALWAYS = 'sempre';

    public function getList(Guests $guest)
    {
        $client = $guest->getClient();

        $qb = $this->createQueryBuilder('e');
        $qb = $qb->select()
            ->leftJoin('e.device', 'device')
            ->where('e.client = :client')
            ->andWhere('e.guest = :guest')
            ->setParameter('client', $client)
            ->setParameter('guest', $guest)
            ->orderBy('e.lastAccess', 'ASC')
        ;

        $statement = $qb->getQuery();

        return $statement->getResult();
    }

    public function getLastAccessByMacAddressAndPeriod(Client $client, $guestMacAddress, $period = null)
    {
        $qb = $this->createQueryBuilder('e');
        $qb = $qb->select()
            ->leftJoin('e.device', 'device')
            ->where('e.client = :client')
            ->andWhere('device.macAddress = :macAddress');

        if ($period && $period != self::PERIOD_ALWAYS) {
            $dateFrom = (new \DateTime("NOW"))
                ->setTimezone(new \DateTimeZone(TimezoneService::UTC))
                ->sub(new \DateInterval("P{$period}D"))
                ->format("Y-m-d H:i:s");

            $qb
                ->andWhere('e.lastAccess >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom, \PDO::PARAM_STR);
        }

        $qb->setParameter('client', $client)
            ->setParameter('macAddress', $guestMacAddress, \PDO::PARAM_STR)
            ->orderBy('e.lastAccess', 'DESC')
            ->setMaxResults(1);

        $statement = $qb->getQuery();

        return $statement->getOneOrNullResult();
    }

    public function updateLastAccess(DeviceEntry $deviceEntry, AccessPoints $accessPoint = null)
    {
        $deviceEntry->updateLastAccessToNow($accessPoint);
        $em = $this->getEntityManager();
        $em->persist($deviceEntry);
        $em->flush();
    }

    public function create(Device $device, Guest $guest, Client $client, AccessPoints $accessPoint = null)
    {
        $em = $this->getEntityManager();

        $guestMysql = $em->getRepository("DomainBundle:Guests")->findOneBy(['id' => $guest->getMysql()]);

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
    }

    public function aggregateDevicesByFilter(array $filter)
    {
        $clientId   = $filter['client_id'];
        $groupBy    = $filter['group_by'];
        $dateFrom   = $filter['date_from'];
        $dateTo     = $filter['date_to'];

        $query = "
            SELECT d.{$groupBy}, count(*) AS total
            FROM devices_entries e
            INNER JOIN devices d ON e.mac_address = d.mac_address
            WHERE e.client_id = $clientId
            AND e.last_access >= '{$dateFrom}'
            AND e.last_access <= '{$dateTo}'
            GROUP BY d.{$groupBy}
        ";

        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->prepare($query);
        $statement->execute();

        return $statement->fetchAll();
    }
}
