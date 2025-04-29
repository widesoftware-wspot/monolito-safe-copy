<?php

namespace Wideti\DomainBundle\Service\SignUp;

use Symfony\Component\HttpFoundation\Session\Session;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Dto\ConfirmationDto;
use Wideti\DomainBundle\Dto\SignUpStatusDto;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Exception\UniqueFieldException;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Guest\GuestServiceAware;
use Wideti\FrontendBundle\Factory\Nas;

class SignUpService
{
    use GuestServiceAware;

    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var CacheServiceImp
     */
    private $cacheService;

    /**
     * @param ConfigurationService $configurationService
     * @param Session $session
     * @param CacheServiceImp $cacheService
     */
    public function __construct(
        ConfigurationService $configurationService,
        Session $session,
        CacheServiceImp $cacheService
    ) {
        $this->configurationService     = $configurationService;
        $this->session                  = $session;
        $this->cacheService             = $cacheService;
    }

	/**
	 * @param Guest|null $guest
	 * @param Nas|null $nas
	 * @param string $locale
	 * @param bool $emailValidate
	 * @return SignUpStatusDto
	 * @throws \Wideti\DomainBundle\Exception\NasWrongParametersException
	 * @throws \Wideti\DomainBundle\Exception\UniqueFieldException
	 */
    public function doSignUp(Guest $guest = null, Nas $nas = null, $locale = "pt_br", $emailValidate = true)
    {
    	$statusReturning = new SignUpStatusDto();

        /**
         * @var Client $client
         */
        $client = $this->session->get(Client::SESSION_KEY);
        if ($client->isEnablePasswordAuthentication()){
            $guest = $this->createWithPassword(
                $nas,
                $guest,
                $locale,
                $emailValidate
            );
        }else{
            $guest = $this->createWithoutPassword(
                $nas,
                $guest,
                $locale,
                $emailValidate
            );
        }

        $statusReturning->setCreatedGuest($guest);

        $confirmation = $this->getConfirmation();

        $statusReturning->setConfirmation($confirmation);

        if (!$confirmation->isConfirmationNeeded()) {
            $this->guestService->confirm($guest, $nas);
        }

        return $statusReturning;
    }

    /**
     * @return ConfirmationDto
     */
    private function getConfirmation()
    {
        $nas    = $this->session->get(Nas::NAS_SESSION_KEY);
        $client = $this->session->get('wspotClient');

        $confirmation = new ConfirmationDto();

        if (!$this->configurationService->get($nas, $client, 'enable_confirmation')) {
	        $confirmation->setIsConfirmationNeeded(false);
	        return $confirmation;
        }

        if ($this->configurationService->get($nas, $client, 'confirmation_sms')) {
            $confirmation->setIsConfirmationNeeded(true);
            $confirmation->setConfirmationType(ConfirmationDto::SMS);
        }

        if ($this->configurationService->get($nas, $client, 'confirmation_email')) {
            $confirmation->setIsConfirmationNeeded(true);
            $confirmation->setConfirmationType(ConfirmationDto::EMAIL);
        }

        return $confirmation;
    }

    /**
     * @param Nas|null $nas
     * @param Guest|null $guest
     * @param string $locale
     * @param bool $emailValidate
     * @return Guest
     */
    private function createWithPassword(
        Nas $nas = null,
        Guest $guest = null,
        $locale = 'pt_br',
        $emailValidate = true
    )
    {
        return $this->guestService->createByFrontend(
            $nas,
            $guest,
            $locale,
            $emailValidate,
            Guest::REGISTER_BY_FORM
        );
    }

    /**
     * @param Nas|null $nas
     * @param Guest|null $guest
     * @param string $locale
     * @param bool $emailValidate
     * @return Guest
     */
    private function createWithoutPassword(
        Nas $nas = null,
        Guest $guest = null,
        $locale = 'pt_br',
        $emailValidate = true
    )
    {
        try {
            return $this->guestService->createByFrontend(
                $nas,
                $guest,
                $locale,
                $emailValidate,
                Guest::REGISTER_BY_FORM
            );
        }catch (UniqueFieldException $e){
            return $this->guestService->updateGuestProperties($guest);
        }
    }
}
