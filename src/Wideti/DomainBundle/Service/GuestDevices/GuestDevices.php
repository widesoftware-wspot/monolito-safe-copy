<?php

namespace Wideti\DomainBundle\Service\GuestDevices;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Guests;
use Wideti\FrontendBundle\Factory\Nas;

interface GuestDevices
{
    public function getDevices(Guests $guests);
    public function getLastAccessWithSpecificDevice(Client $client, $guestMacAddress, $period = null);
    public function updateLastAccess(Nas $nas, Guest $guest, Client $client);
    public function getGuestsByMacDevice(Client $client, $macAddress);
    public function hasGuestByMacAddressAndGuestId(Client $client, $macAddress, $guestId);
    public function graphAccessData(Client $client, $filterRangeDate = null);
    public function accessData(Client $client, $type, $filterRangeDate = null);
    public function accessDataInfo();
}
