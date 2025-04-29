<?php

namespace Wideti\DomainBundle\Service\MacAddressAuthentication;

use Wideti\DomainBundle\Analytics\EventLoggerManager;
use Wideti\DomainBundle\Analytics\Events\Event;
use Wideti\DomainBundle\Analytics\Events\EventIdentifier;
use Wideti\DomainBundle\Analytics\Events\EventType;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Repository\Elasticsearch\Radacct\RadacctRepository;
use Wideti\DomainBundle\Service\Guest\GuestService;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\FormAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class MacAddressAuthenticationImp implements MacAddressAuthentication
{
    use FormAware;
    use TwigAware;

    /**
     * @var RadacctRepository
     */
    private $radacctRepository;
    /**
     * @var GuestService
     */
    private $guestService;
    /**
     * @var EventLoggerManager
     */
    private $logManager;

    /**
     * MacAddressAuthenticationImp constructor.
     * @param RadacctRepository $radacctRepository
     * @param GuestService $guestService
     */
    public function __construct(
        RadacctRepository $radacctRepository,
        GuestService $guestService,
        EventLoggerManager $logManager
    ) {
        $this->radacctRepository = $radacctRepository;
        $this->guestService = $guestService;
        $this->logManager = $logManager;
    }

    /**
     * @param Client $client
     * @param Nas $nas
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function process(Client $client, Nas $nas)
    {
        $onlineGuest = $this->radacctRepository->getOnlineAccountingByGuestMacAddress(
            $client,
            $nas->getGuestDeviceMacAddress()
        );

        if ($onlineGuest) {
            $guestId = $onlineGuest['username'];
            /**
             * @var Guest $guest
             */
            $guest = $this->guestService->findGuestByMacAddressAndGuestId($nas->getGuestDeviceMacAddress(), $guestId);

            if ($guest) {
                $username = "#{$client->getId()}#{$guest->getLastPolicyIdCreated()}";
                $password = $guest->getPassword();

                $form = $this->form->createNamed(
                    '',
                    $nas->getVendorName(),
                    null,
                    [
                        'action'    => $nas->getNasFormPost()->getPostFormUrl(),
                        'username'  => $username,
                        'password'  => $password
                    ]
                );

                $analiticEvent = new Event();
                $event = $analiticEvent->withClient($client)
                    ->withEventIdentifier(EventIdentifier::MAC_LOGIN_SIGN_IN)
                    ->withEventType(EventType::MAC_LOGIN_SIGN_IN)
                    ->withNas($nas)
                    ->withRequest(null)
                    ->withSession(null)
                    ->withExtraData(null)
                    ->build();

                $this->logManager->sendLog($event);

                return $this->render(
                    'FrontendBundle:General:formPostControllerForMacAuthentication.html.twig',
                    [
                        'wspotNas'  => $nas,
                        'password'  => $password,
                        'form'      => $form->createView()
                    ]
                );
            }
        }
    }
}
