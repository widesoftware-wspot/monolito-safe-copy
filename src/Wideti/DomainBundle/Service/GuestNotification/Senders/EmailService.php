<?php

namespace Wideti\DomainBundle\Service\GuestNotification\Senders;

use phpDocumentor\Reflection\Types\This;
use Symfony\Component\HttpFoundation\Session\Session;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Mail\MailHeaderServiceAware;
use Wideti\DomainBundle\Service\Mailer\MailerServiceAware;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessageBuilder;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\DomainBundle\Helpers\EncryptDecryptHelper;

class EmailService
{
    use MailerServiceAware;
    use MailHeaderServiceAware;
    use TwigAware;
    use EntityManagerAware;

    protected $whiteLabel;
    /**
     * @var ConfigurationService
     */
    private $configurationService;
    /**
     * @var Session
     */
    private $session;

    /**
     * @param $whiteLabel
     * @param ConfigurationService $configurationService
     * @param Session $session
     */
    public function __construct(
        $whiteLabel,
        ConfigurationService $configurationService,
        Session $session
    ) {
        $this->whiteLabel           = $whiteLabel;
        $this->configurationService = $configurationService;
        $this->session              = $session;
    }

    public function welcome(Guest $guest, Nas $nas = null, $params)
    {
        $client = $this->em
            ->getRepository('DomainBundle:Client')
            ->findOneByDomain($params['domain']);

        $guestEmail     = $guest->getProperties()['email'];
        $locale         = strtolower($guest->getLocale());
        $companyName    = $this->whiteLabel['companyName'];
        $station        = $this->configurationService->get($nas, $client, 'partner_name');
        $apMacAddress   = (array_key_exists('macAddress', $params) ? $params['macAddress'] : '');

        if ($apMacAddress !== null) {
            $accessPoint = $this->em
                ->getRepository("DomainBundle:AccessPoints")
                ->getAccessPoint($apMacAddress, $client);

            if ($accessPoint && $accessPoint->getLocal()) {
                $station = $accessPoint->getLocal();
            }
        }

        $isWhiteLabel = false;
        $fromEmail = $this->emailHeader->getSender();

        if ($client->isWhiteLabel()) {
            $isWhiteLabel = true;
            $fromEmail = $this->configurationService->get($nas, $client, 'from_email');
            $fromEmail = isset($fromEmail) ? $fromEmail : $this->emailHeader->getSender();
        }

        $emailSubject   = $isWhiteLabel?
            "{$companyName} - Seja bem-vindo! Você se cadastrou na rede Wi-Fi da(o) {$station}.":
            "Mambo WiFi - Seja bem-vindo! Você se cadastrou na rede Wi-Fi da(o) {$station}.";
        $emailFromTitle = "Cadastro de visitantes";
        $emailTemplate  = "emailBemVindo";

        if (!is_null($locale) && $locale == 'en' || $locale == 'en_us') {
            $emailSubject   = $isWhiteLabel?
                "{$companyName} - Welcome! You registered at {$station} Internet Wi-Fi":
                "Mambo WiFi - Welcome! You registered at {$station} Internet Wi-Fi";
            $emailFromTitle = "Visitor Registration";
            $emailTemplate  = "emailBemVindoEng";
        } elseif (!is_null($locale) && $locale == 'es') {
            $emailSubject   = $isWhiteLabel?
                "{$companyName} - Sea bienvenido! Te has registrado en la red Wi-Fi de {$station}":
                "Mambo WiFi - Sea bienvenido! Te has registrado en la red Wi-Fi de {$station}";
            $emailFromTitle = "Registro de visitante";
            $emailTemplate  = "emailBemVindoEs";
        }

        $builder = new MailMessageBuilder();

        $message = $builder
            ->subject($emailSubject)
            ->from([$emailFromTitle => $fromEmail])
            ->to([
                [$guestEmail]
            ])
            ->htmlMessage(
                $this->renderView(
                    "AdminBundle:Guests:{$emailTemplate}.html.twig",
                    [
                        'guest'     => $guest,
                        'locale'    => $locale,
                        'station'   => $station,
                        'fromEmail' => $this->configurationService->get($nas, $client, 'from_email'),
                        'loginField'        => $params['loginField'][0],
                        'loginFieldValue'   => $guest->getProperties()[$guest->getLoginField()],
                        'isWhiteLabel'      => $isWhiteLabel,
                        'whiteLabel'        => $this->whiteLabel
                    ]
                )
            )
            ->build()
        ;


        if ($this->configurationService->get($nas, $client, 'from_email')) {
            $builder->replyTo($this->configurationService->get($nas, $client, 'from_email'));
        }

        $this->mailerService->send($message);
    }

    public function confirmation(Guest $guest, Nas $nas = null, $content, $params)
    {
        $client = $this->em
            ->getRepository('DomainBundle:Client')
            ->findOneByDomain($params['domain']);

        $locale         = strtolower($guest->getLocale());
        $companyName    = $this->whiteLabel['companyName'];
        $station        = $this->configurationService->get($nas, $client, 'partner_name');
        $apMacAddress   = (array_key_exists('macAddress', $params) ? $params['macAddress'] : '');

        if ($apMacAddress !== null) {
            $accessPoint = $this->em
                ->getRepository("DomainBundle:AccessPoints")
                ->getAccessPoint($apMacAddress, $client);

            if ($accessPoint && $accessPoint->getLocal()) {
                $station = $accessPoint->getLocal();
            }
        }

        $isWhiteLabel = false;
        $fromEmail = $this->emailHeader->getSender();
        if ($client->isWhiteLabel()) {
            $isWhiteLabel = true;
            $fromEmail = $this->configurationService->get($nas, $client, 'from_email');
            $fromEmail = isset($fromEmail) ? $fromEmail : $this->emailHeader->getSender();
        }

        $emailSubject   = $isWhiteLabel ?
            "O Wi-Fi está te esperando... Confirme seu cadastro no Hotspot":
            "O Wi-Fi está te esperando... Confirme seu cadastro no Mambo WiFi";

        $emailFromTitle = "Confirmação de cadastro por e-mail";
        $emailTemplate  = "urlConfirmationEmail";

        if (!is_null($locale) && $locale == 'en' || $locale == 'en_us') {
            $emailSubject   = $isWhiteLabel ?
                "The Wi-Fi is waiting for you! Confirm your registration on Hotspot now!":
                "The Wi-Fi is waiting for you! Confirm your registration on Mambo WiFi now!";
            $emailFromTitle = "Registration Confirmation by Email";
            $emailTemplate  = "urlConfirmationEmailEng";
        } elseif (!is_null($locale) && $locale == 'es') {
            $emailSubject   = $isWhiteLabel?
                "Wi-Fi te aguarda...Confirma tu registro en Hotspot!":
                "Wi-Fi te aguarda...Confirma tu registro en Mambo WiFi!";
            $emailFromTitle = "Confirmación de registro por correo electrónico";
            $emailTemplate  = "urlConfirmationEmailEs";
        }

        $builder = new MailMessageBuilder();
        $message = $builder
            ->subject($emailSubject)
            ->from([$emailFromTitle => $fromEmail])
            ->to([
                [$guest->getProperties()['email']]
            ])
            ->htmlMessage(
                $this->renderView(
                    "AdminBundle:Guests:{$emailTemplate}.html.twig",
                    [
                        'guest'     => $guest,
                        'station'   => $station,
                        'text'      => $content,
                        'fromEmail' => $this->configurationService->get($nas, $client, 'from_email'),
                        'companyName' => $companyName,
                        'isWhiteLabel' => $isWhiteLabel
                    ]
                )
            )
            ->build()
        ;

        if ($this->configurationService->get($nas, $client, 'from_email')) {
            $builder->replyTo($this->configurationService->get($nas, $client, 'from_email'));
        }

        $this->mailerService->send($message);
    }

    public function resendConfirmation(Guest $guest, Nas $nas = null, $content, $params)
    {
        $client = $this->em
            ->getRepository('DomainBundle:Client')
            ->findOneByDomain($params['domain']);

        $locale         = strtolower($guest->getLocale());
        $companyName    = $this->whiteLabel['companyName'];
        $station        = $this->configurationService->get($nas, $client, 'partner_name');
        $apMacAddress   = (array_key_exists('macAddress', $params) ? $params['macAddress'] : '');

        if ($apMacAddress !== null) {
            $accessPoint = $this->em
                ->getRepository("DomainBundle:AccessPoints")
                ->getAccessPoint($apMacAddress, $client);

            if ($accessPoint && $accessPoint->getLocal()) {
                $station = $accessPoint->getLocal();
            }
        }

        $isWhiteLabel = false;
        $fromEmail = $this->emailHeader->getSender();

        if ($client->isWhiteLabel()) {
            $isWhiteLabel = true;
            $fromEmail = $this->configurationService->get($nas, $client, 'from_email');
            $fromEmail = isset($fromEmail) ? $fromEmail : $this->emailHeader->getSender();
        }


        $emailSubject   = "Confirmação de Cadastro - {$station}";
        $emailFromTitle = "Cadastro - {$companyName}";
        $emailTemplate  = "urlConfirmationEmail";

        if (!is_null($locale) && $locale == 'en' || $locale == 'en_us') {
            $emailSubject   = "Confirmation Step - {$station}";
            $emailFromTitle = "Registration - {$companyName}";
            $emailTemplate  = "urlConfirmationEmailEng";
        } elseif (!is_null($locale) && $locale == 'es') {
            $emailSubject   = "Confirmación de Registro - {$station}";
            $emailFromTitle = "Cadastro - {$companyName}";
            $emailTemplate  = "urlConfirmationEmailEs";
        }

        $builder = new MailMessageBuilder();
        $message = $builder
            ->subject($emailSubject)
            ->from([$emailFromTitle => $fromEmail])
            ->to([
                [$guest->getProperties()['email']]
            ])
            ->htmlMessage(
                $this->renderView(
                    "AdminBundle:Guests:{$emailTemplate}.html.twig",
                    [
                        'guest'     => $guest,
                        'station'   => $station,
                        'text'      => $content,
                        'fromEmail' => $this->configurationService->get($nas, $client, 'from_email'),
                        'isWhiteLabel' => $isWhiteLabel,
                        'companyName' => $companyName
                    ]
                )
            )
            ->build()
        ;

        if ($this->configurationService->get($nas, $client, 'from_email')) {
            $builder->replyTo($this->configurationService->get($nas, $client, 'from_email'));
        }

        $this->mailerService->send($message);
    }

    public function password(Guest $guest, Nas $nas = null, $params)
    {
        $client = $this->em
            ->getRepository('DomainBundle:Client')
            ->findOneByDomain($params['domain']);

        $guestEmail     = $guest->getProperties()['email'];
        $locale         = strtolower($guest->getLocale());
        $companyName    = $this->whiteLabel['companyName'];
        $station        = $this->configurationService->get($nas, $client, 'partner_name');

        if ($guest->getRegistrationMacAddress()) {
            $accessPoint = $this->em
                ->getRepository('DomainBundle:AccessPoints')
                ->getAccessPointByIdentifier($guest->getRegistrationMacAddress(), $client);

            if ($accessPoint && $accessPoint[0]->getLocal()) {
                $station = $accessPoint[0]->getLocal();
            }
        }

        $isWhiteLabel = false;
        $fromEmail = $this->emailHeader->getSender();

        if ($client->isWhiteLabel()) {
            $isWhiteLabel = true;
            $fromEmail = $this->configurationService->get($nas, $client, 'from_email');
            $fromEmail = isset($fromEmail) ? $fromEmail : $this->emailHeader->getSender();
        }


        $emailSubject   = $isWhiteLabel?
            "{$companyName} - Alteração de senha":
            "Mambo WiFi - Alteração de senha";
        $emailFromTitle = "Troca de senha - Visitantes";
        $emailTemplate  = "NewPasswordEmail";

        if (!is_null($locale) && $locale == 'en' || $locale == 'en_us') {
            $emailSubject   = $isWhiteLabel?
                "{$companyName} - Password Change":
                "Mambo WiFi - Password Change";
            $emailFromTitle = "Password Change - Visitors";
            $emailTemplate  = "NewPasswordEmailEng";
        } elseif (!is_null($locale) && $locale == 'es') {
            $emailSubject   = $isWhiteLabel?
                "{$companyName} - Cambio de contraseña":
                "WSpot - Cambio de contraseña";
            $emailFromTitle = "Cambio de contraseña - Visitantes";
            $emailTemplate  = "NewPasswordEmailEs";
        }
        $password = EncryptDecryptHelper::decrypt($params['password'], $guestEmail);
        $changeByAdmin = (array_key_exists('changePasswordByAdmin', $params) ? $params['changePasswordByAdmin'] : false );
        $builder = new MailMessageBuilder();
        $message = $builder
            ->subject($emailSubject)
            ->from([$emailFromTitle => $fromEmail])
            ->to([
                [$guestEmail]
            ])
            ->htmlMessage(
                $this->renderView(
                    "AdminBundle:Guests:{$emailTemplate}.html.twig",
                    [
                        'password'      => $password,
                        'name'          => $guest->get('name'),
                        'email'         => $guestEmail,
                        'station'       => $station,
                        'companyName'   => $companyName,
                        'fromEmail'     => $this->configurationService->get($nas, $client, 'from_email'),
                        'changeByAdmin' => $changeByAdmin,
                        'whiteLabel'    => $this->whiteLabel,
                        'isWhiteLabel'  => $isWhiteLabel,
                        'resetPassword' => $guest->getResetPassword()
                    ]
                )
            )
            ->build()
        ;

        if ($this->configurationService->get($nas, $client, 'from_email')) {
            $builder->replyTo($this->configurationService->get($nas, $client, 'from_email'));
        }

        $this->mailerService->send($message);
    }
}
