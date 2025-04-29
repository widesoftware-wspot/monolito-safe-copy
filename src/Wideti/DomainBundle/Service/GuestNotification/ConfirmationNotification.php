<?php

namespace Wideti\DomainBundle\Service\GuestNotification;

use Wideti\DomainBundle\Exception\NasWrongParametersException;
use Wideti\DomainBundle\Helpers\NasHelper;
use Symfony\Component\Routing\Router;
use Wideti\DomainBundle\Service\AuthorizationCode\AuthorizationCodeServiceAware;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Guest\GuestServiceAware;
use Wideti\DomainBundle\Service\GuestNotification\Base\EmailNotificationInterface;
use Wideti\DomainBundle\Service\GuestNotification\Base\SMSNotificationInterface;
use Wideti\DomainBundle\Service\GuestNotification\Senders\EmailServiceAware;
use Wideti\DomainBundle\Service\GuestNotification\Senders\SmsServiceAware;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\DomainBundle\Service\NasManager\NasServiceAware;
use Wideti\DomainBundle\Service\Sms\Dto\SmsDto;
use Wideti\DomainBundle\Service\Sms\Dto\SmsBuilder;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\RouterAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class ConfirmationNotification implements EmailNotificationInterface, SMSNotificationInterface
{
    use EntityManagerAware;
    use MongoAware;
    use EmailServiceAware;
    use SmsServiceAware;
    use AuthorizationCodeServiceAware;
    use RouterAware;
    use ModuleAware;
    use SessionAware;
    use NasServiceAware;
    use GuestServiceAware;

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * ConfirmationNotification constructor.
     * @param ConfigurationService $configurationService
     */
    public function __construct(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    public function send(Nas $nas = null, $params = [])
    {
        $client = $this->em
            ->getRepository('DomainBundle:Client')
            ->findOneByDomain($params['domain']);

        $guest = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneBy([
                'id' => $params['guestId']
            ])
        ;

        if ($this->configurationService->get($nas, $client, 'confirmation_email') == 1
            && $this->guestService->hasEmailFieldInProperties($guest)) {
            $content = $this->authorizationCodeService->create('hash', $guest);

            if (!$nas || !($nas instanceof Nas)) {
                $className = get_class($nas);
                throw new NasWrongParametersException(
                    "NAS nulo na classe ConfirmationNotification: {$className}"
                );
            }

            $link = $this->router->generate(
                'frontend_confirm_url',
                [
                    'token'         => $content,
                    'vendorName'    => $nas->getVendorName(),
                    'nasRaw'        => $nas->getVendorRawParameters()
                ],
                Router::ABSOLUTE_URL
            );

            $locale = $params['locale'];

            $content = "<a href='{$link}'>confirmar</a>";
            $content .= "<br><p style='font-size: 20px;'>Caso o botão acima não funcione, você pode colar esse link no seu navegador</p>";
            $content .= "<p style='font-size: 12px; line-height: 13px;'>".$link."<p>";

            if (!is_null($locale) && $locale == 'en' || $locale == 'en_us') {
                $content = "<a href='{$link}'>confirm</a>";
                $content .= "<br><p style='font-size: 20px;'>If the button does not work paste this link link below in your browser</p>";
                $content .= "<p style='font-size: 12px; line-height: 13px;'>".$link."<p>";

            } elseif (!is_null($locale) && $locale == 'es') {
                $content = "<a href='{$link}'>confirmar</a>";
                $content .= "<br><p style='font-size: 20px;'>Si el botón no funcionar adicione el link abajo a su browser</p>";
                $content .= "<p style='font-size: 12px; line-height: 13px;'>".$link."<p>";
            }

            $configMessage = $this->configurationService->get($nas, $client, 'content_confirmation_email_pt');

            if ($params['locale'] == 'en') {
                $configMessage = $this->configurationService->get($nas, $client, 'content_confirmation_email_en');
            }

            if ($params['locale'] == 'es') {
                $configMessage = $this->configurationService->get($nas, $client, 'content_confirmation_email_es');
            }

            $partnerName = null;

            if ($this->configurationService->get($nas, $client, 'partner_name')) {
                $partnerName = " - ".$this->configurationService->get($nas, $client, 'partner_name');
            }

            $message = str_replace(
                '{ nome_da_empresa }',
                $partnerName,
                str_replace(
                    '{ url }',
                    $content,
                    $configMessage
                )
            );

	        $this->emailService->confirmation($guest, $nas, $message, $params);

        }
    }

    public function sendSMS(Nas $nas = null, $params = [])
    {
        $client = $this->getLoggedClient();
        $guest  = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneBy([
                'id' => $params['guestId']
            ])
        ;

        if (!$guest) {
            throw new \Exception("Guest not found: ConfirmationNotification.php");
        }

        $hasSMSConfirmation = $this->configurationService->get($nas, $client, 'confirmation_sms');

        $oauthRequires2FA = false;
        $oauthClientId = $this->session->get('oauthClientId');
        if ($oauthClientId) {
            $oauthRequires2FA = $this->em->getRepository("DomainBundle:OAuthLogin")->findOneBy([
                'domain' => $client->getDomain(),
                'clientId' => $oauthClientId,
                'twoFactorRequired' => true
            ]);
        }

        if (($hasSMSConfirmation == 1 || $oauthRequires2FA)) {
            $content        = $this->authorizationCodeService->create('code', $guest);
            $configMessage  = $this->configurationService->get($nas, $client, 'content_confirmation_sms_pt');

            if ($params['locale'] == 'en') {
                $configMessage = $this->configurationService->get($nas, $client, 'content_confirmation_sms_en');
            }

            if ($params['locale'] == 'es') {
                $configMessage = $this->configurationService->get($nas, $client, 'content_confirmation_sms_es');
            }

            $partnerName = null;

            if ($this->configurationService->get($nas, $client, 'partner_name')) {
                $partnerName = " - ".$this->configurationService->get($nas, $client, 'partner_name');
            }

            $message = str_replace(
                '{ nome_da_empresa }',
                $partnerName,
                str_replace(
                    '{ codigo }',
                    $content,
                    $configMessage
                )
            );

            $builder    = new SmsBuilder();
            $smsBuilder = $builder
                ->withContent($message)
                ->withType(SmsDto::CONFIRM_REGISTRATION)
                ->build();

	        $this->smsService->send($smsBuilder, $guest);
        }
    }
}
