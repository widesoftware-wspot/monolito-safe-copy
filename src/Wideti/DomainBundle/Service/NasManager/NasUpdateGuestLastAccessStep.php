<?php
namespace Wideti\DomainBundle\Service\NasManager;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\GuestDevices\GuestDevices;
use Wideti\FrontendBundle\Factory\Nas;

/**
 * Class NasUpdateGuestLastAccessStep
 * @package Wideti\DomainBundle\Service\NasManager
 */
class NasUpdateGuestLastAccessStep implements NasStepInterface
{
    /**
     * @var GuestDevices
     */
    private $guestDevices;

    /**
     * NasUpdateGuestLastAccessStep constructor.
     * @param GuestDevices $guestDevices
     */
    public function __construct(
        GuestDevices $guestDevices
    ) {
        $this->guestDevices = $guestDevices;
    }

    /**
     * @param Guest $guest
     * @param Nas|null $nas
     * @param Client $client
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function process(Guest $guest, Nas $nas = null, Client $client)
    {
        return $this->guestDevices->updateLastAccess($nas, $guest, $client);
    }
}
