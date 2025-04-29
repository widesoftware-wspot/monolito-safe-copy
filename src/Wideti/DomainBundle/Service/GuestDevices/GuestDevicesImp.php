<?php

namespace Wideti\DomainBundle\Service\GuestDevices;

use Doctrine\ORM\EntityManager;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\AccessPoints;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Device;
use Wideti\DomainBundle\Entity\DeviceEntry;
use Wideti\DomainBundle\Entity\Guests;
use Wideti\DomainBundle\Helpers\DeviceHelper;
use Wideti\DomainBundle\Repository\AccessPointsRepository;
use Wideti\DomainBundle\Repository\DeviceAccessRepository;
use Wideti\DomainBundle\Repository\DeviceEntryRepository;
use Wideti\DomainBundle\Repository\DeviceRepository;
use Wideti\FrontendBundle\Factory\Nas;

class GuestDevicesImp implements GuestDevices
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var DeviceRepository
     */
    private $deviceRepository;
    /**
     * @var DeviceEntryRepository
     */
    private $deviceEntryRepository;

    /**
     * @var AccessPointsRepository
     */
    private $accessPointsRepository;

    /**
     * GuestDevicesImp constructor.
     * @param EntityManager $em
     * @param DeviceRepository $deviceRepository
     * @param DeviceEntryRepository $deviceEntryRepository
     * @param AccessPointsRepository $accessPointsRepository
     */
    public function __construct(
        EntityManager $em,
        DeviceRepository $deviceRepository,
        DeviceEntryRepository $deviceEntryRepository,
        AccessPointsRepository $accessPointsRepository
    ) {
        $this->em = $em;
        $this->deviceRepository = $deviceRepository;
        $this->deviceEntryRepository = $deviceEntryRepository;
        $this->accessPointsRepository = $accessPointsRepository;
    }

    public function getDevices(Guests $guest)
    {
        return $this->deviceEntryRepository->getList($guest);
    }

    public function getLastAccessWithSpecificDevice(Client $client, $guestMacAddress, $period = null)
    {
        return $this->deviceEntryRepository->getLastAccessByMacAddressAndPeriod(
            $client,
            $guestMacAddress,
            $period
        );
    }

    public function updateLastAccess(Nas $nas, Guest $guest, Client $client)
    {
        $clientId           = $client->getId();
        $guestId            = $guest->getMysql();
        $guestMacAddress    = $nas->getGuestDeviceMacAddress();
        $identifier         = $nas->getAccessPointMacAddress();

        /**
         * @var AccessPoints $accessPoint
         */
        $accessPoint = $this->accessPointsRepository->findOneBy([
            'client'        => $clientId,
            'identifier'    => $identifier
        ]);

        /**
         * @var DeviceEntry $deviceEntry
         */
        $deviceEntry = $this->deviceEntryRepository->findOneBy([
            'device'    => $guestMacAddress,
            'guest'     => $guestId,
            'client'    => $clientId
        ]);

        if ($deviceEntry) {
            $this->deviceEntryRepository->updateLastAccess($deviceEntry, $accessPoint);
        } else {
            $device = $this->deviceRepository->findOneBy([
                'macAddress' => $nas->getGuestDeviceMacAddress()
            ]);

            if (!$device) {
                $u_agent = $_SERVER['HTTP_USER_AGENT'];
                $userAgent = DeviceHelper::getAccessDataInfo($u_agent);

                $newDevice = new Device(
                    $guestMacAddress,
                    !empty($userAgent['os']) ? $userAgent['os'] : Device::UNKNOWN,
                    !empty($userAgent['device']) ? $userAgent['device'] : Device::UNKNOWN
                );

                $this->deviceRepository->createDeviceAndEntryByTheSameTransaction(
                    $newDevice,
                    $guest,
                    $client,
                    $accessPoint
                );
            } else {
                $this->deviceEntryRepository->create($device, $guest, $client, $accessPoint);
            }
        }
    }

    public function getGuestsByMacDevice(Client $client, $macAddress)
    {
        $ids     = [];
        $entries = $this->deviceEntryRepository->findBy([
            'client' => $client,
            'device' => $macAddress
        ]);

        foreach ($entries as $entry) {
            array_push($ids, $entry->getGuest()->getId());
        }

        return $ids;
    }

    public function hasGuestByMacAddressAndGuestId(Client $client, $macAddress, $guestId)
    {
        return $this->deviceEntryRepository->findOneBy([
            'client' => $client,
            'device' => $macAddress,
            'guest' => $guestId
        ]);
    }

    public function graphAccessData(Client $client, $filterRangeDate = null)
    {
        $guestPlatformAccessData    = $this->accessData($client, 'platform', $filterRangeDate);
        $guestDeviceAccessData      = $this->accessData($client, 'os', $filterRangeDate);

        $accessData['platformData'] = [];
        $accessData['deviceData']   = [];

        foreach ($guestPlatformAccessData as $data) {
            foreach ($data as $value) {
                array_push(
                    $accessData['platformData'],
                    [
                        'label' => $value['platform'],
                        'data'  => (integer)$value['total']
                    ]
                );
            }
        }

        foreach ($guestDeviceAccessData as $data) {
            foreach ($data as $value) {
                array_push(
                    $accessData['deviceData'],
                    [
                        'label' => $value['os'],
                        'data'  => (integer)$value['total']
                    ]
                );
            }
        }

        return $accessData;
    }

    public function accessData(Client $client, $type, $filterRangeDate = null)
    {
        if ($filterRangeDate) {
            $dateFrom   = $filterRangeDate['date_from'];
            $dateTo     = $filterRangeDate['date_to'];
        } else {
            $dateFrom   = date_format(new \DateTime('now -10 years'), 'Y-m-d 00:00:00');
            $dateTo     = date_format(new \DateTime('now'), 'Y-m-d 00:00:00');
        }

        $filter = [
            "client_id" => $client->getId(),
            "group_by"  => $type,
            "date_from" => $dateFrom,
            "date_to"   => $dateTo
        ];

        $devices = $this->deviceEntryRepository->aggregateDevicesByFilter($filter);
        $accessData[$type] = $devices;

        return $accessData;
    }

    public function accessDataInfo()
    {
        $u_agent = $_SERVER['HTTP_USER_AGENT'];
        return DeviceHelper::getAccessDataInfo($u_agent);
    }
}
