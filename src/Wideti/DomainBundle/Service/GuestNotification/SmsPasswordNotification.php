<?php

namespace Wideti\DomainBundle\Service\GuestNotification;

use Symfony\Component\HttpFoundation\Session\Session;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationAware;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\GuestNotification\Base\EmailNotificationInterface;
use Wideti\DomainBundle\Service\GuestNotification\Base\SMSNotificationInterface;
use Wideti\DomainBundle\Service\GuestNotification\Senders\SmsServiceAware;
use Wideti\DomainBundle\Service\Sms\Dto\SmsDto;
use Wideti\DomainBundle\Service\Sms\Dto\SmsBuilder;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\MongoAware;

class SmsPasswordNotification implements SMSNotificationInterface
{
    use MongoAware;
    use SmsServiceAware;

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
    public function __construct(
        ConfigurationService $configurationService,
        Session $session
    ) {
        $this->configurationService = $configurationService;
        $this->session              = $session;
    }

    public function sendSMS(Nas $nas = null, $params = [])
    {
        $client = $this->session->get('wspotClient');

        $guest  = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneBy([
                'id' => $params['guestId']
            ]);

        $locale  = strtolower($guest->getLocale());
        $message = "";

        if (!is_null($locale) && $locale == 'pt_br') {
            $message = $this->configurationService->get($nas, $client, 'content_welcome_sms_pt');
        } elseif (!is_null($locale) && $locale == 'en' || $locale == 'en_us') {
            $message = $this->configurationService->get($nas, $client, 'content_welcome_sms_en');
        } elseif (!is_null($locale) && $locale == 'es') {
            $message = $this->configurationService->get($nas, $client, 'content_welcome_sms_es');
        }

        $builder = new SmsBuilder();
        $smsBuilder = $builder
            ->withContent($message)
            ->withType(SmsDto::WELCOME)
            ->build();

        $this->smsService->send($smsBuilder, $guest);
    }
}
