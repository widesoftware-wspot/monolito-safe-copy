<?php
namespace Wideti\DomainBundle\Service\SignIn;

use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Dto\OneGuestQueryDto;
use Wideti\DomainBundle\Dto\SignInStatusDto;
use Wideti\DomainBundle\Event\AuthenticationEvent;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationAware;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Guest\GuestServiceAware;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\DomainBundle\Service\NasManager\NasServiceAware;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\EventDispatcherAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class SignInService
{
    use EntityManagerAware;
    use MongoAware;
    use TranslatorAware;
    use GuestServiceAware;
    use SessionAware;
    use ModuleAware;
    use EventDispatcherAware;
    use NasServiceAware;

    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var CacheServiceImp
     */
    private $cacheService;

    /**
     * SignInService constructor.
     * @param ConfigurationService $configurationService
     * @param CacheServiceImp $cacheService
     */
    public function __construct(ConfigurationService $configurationService, CacheServiceImp $cacheService)
    {
        $this->configurationService = $configurationService;
        $this->cacheService         = $cacheService;
    }

    public function doSignIn($accessData, $nas)
    {
        $client         = $this->session->get('wspotClient');
        $guest          = $this->checkIfGuestExist($accessData);
        $signInStatus   = new SignInStatusDto();

        if ($guest->getEmailIsValid() == false && $guest->getStatus() == Guest::STATUS_INACTIVE) {
            $signInStatus->setStatus(SignInStatusDto::EMAIL_IS_INVALID);
            return $signInStatus;
        }

        if ($this->configurationService->get($nas, $client, 'confirmation_sms')
            || $this->configurationService->get($nas, $client, 'confirmation_email')) {
            $this->session->set('edit', $guest->getMysql());
            $this->session->set('confirmationType', 'sms');

            if (!$this->configurationService->get($nas, $client, 'confirmation_sms') &&
                $this->configurationService->get($nas, $client, 'confirmation_email') &&
                $this->moduleService->checkModuleIsActive('access_code') === false
            ) {
                $this->session->set('confirmationType', 'email');
            }

            if ($guest->getStatus() == Guest::STATUS_BLOCKED) {
                $signInStatus->setStatus(SignInStatusDto::SIGNIN_WITH_CONFIRMATION);

                if ($this->guestService->verifyUserBlockPerTime($guest) === false) {
                    $signInStatus->setStatus(SignInStatusDto::SIGNIN_WITH_CONFIRMATION_BLOCKED);
                }

                return $signInStatus;
            }

            if ($guest->getStatus() == Guest::STATUS_PENDING_APPROVAL) {
                $signInStatus->setStatus(SignInStatusDto::SIGNIN_WITH_CONFIRMATION);

                if ($this->guestService->verifyUserBlockPerTime($guest) === true) {
                    $signInStatus->setStatus(SignInStatusDto::SIGNIN_WITH_CONFIRMATION_BLOCKED);
                }

                return $signInStatus;
            }
        }

        $this->guestService->checkCredentials($guest->getProperties(), $accessData->getPassword(), $nas);

        $this->eventDispatcher->dispatch('core.event.authentication', new AuthenticationEvent($guest, 'form'));

        $signInStatus->setStatus(SignInStatusDto::SIGNIN_SUCCESS);
        $signInStatus->setGuest($guest);

        return $signInStatus;
    }

    /**
     * @param $accessData
     * @return null|object
     */
    private function checkIfGuestExist($accessData)
    {
        $client = $this->getLoggedClient();
        $loginField  = $accessData->getProperties();
        $oneGuestDto = new OneGuestQueryDto();
        $oneGuestDto->setProperty(key($loginField));
        $oneGuestDto->setValue(array_values($loginField)[0]);
        $guest = $this->guestService->getOneGuest($oneGuestDto);

        $error = ($client->getDomain() === 'kopclub')
            ? $this->translator->trans('wspot.login_page.login_wrong_data_1')
            : $this->translator->trans('wspot.login_page.login_wrong_data')
        ;

        if ($guest === null) {
            throw new AuthenticationCredentialsNotFoundException($error);
        }

        return $guest;
    }
}
