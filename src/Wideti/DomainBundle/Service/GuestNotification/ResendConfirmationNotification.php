<?php

namespace Wideti\DomainBundle\Service\GuestNotification;

use Symfony\Component\HttpFoundation\Session\Session;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Guest\GuestServiceAware;
use Symfony\Component\Routing\Router;
use Wideti\DomainBundle\Service\GuestNotification\Base\EmailNotificationInterface;
use Wideti\DomainBundle\Service\GuestNotification\Senders\EmailServiceAware;
use Wideti\DomainBundle\Service\GuestNotification\Senders\SmsServiceAware;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\RouterAware;

class ResendConfirmationNotification implements EmailNotificationInterface
{
    use EntityManagerAware;
    use MongoAware;
    use EmailServiceAware;
    use SmsServiceAware;
    use RouterAware;
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
     * @param ConfigurationService $configurationService
     * @param Session $session
     */
    public function __construct(ConfigurationService $configurationService, Session $session)
    {
        $this->configurationService = $configurationService;
        $this->session              = $session;
    }

    public function send(Nas $nas = null, $params = [])
    {

        $guest = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneBy([
                'id' => $params['guestId']
            ])
        ;

        $client = $this->session->get('wspotClient');

        if ($this->guestService->hasEmailFieldInProperties($guest)) {
            $code = $this->em
                ->getRepository('DomainBundle:GuestAuthCode')
                ->findOneByGuest($guest->getMysql());

            $link = $this->router->generate(
                            'frontend_confirm_url',
                            [
                                'token'      => $code->getCode(),
                                'vendorName' => $nas->getVendorName(),
                                'nasRaw'     => $guest->getNasRaw()
                            ],
                            Router::ABSOLUTE_URL
                        );


            $locale = $params['locale'];

            $content = "<a href='{$link}'>confirmar</a>";
            $content .= "<br><p style='font-size: 20px;'>Se o o botão não funcionar cole o link abaixo em seu navegador</p>";
            $content .= "<p style='font-size: 12px; line-height: 13px;'>".$link."<p>";

            if (!is_null($locale) && $locale == 'en' || $locale == 'en_us') {
                $content = "<a href='{$link}'>Register</a>";
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

            $this->emailService->resendConfirmation($guest, $nas, $message, $params);
        }
    }
}
