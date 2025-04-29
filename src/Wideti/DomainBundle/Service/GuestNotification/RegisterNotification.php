<?php

namespace Wideti\DomainBundle\Service\GuestNotification;

use Symfony\Component\HttpFoundation\Session\Session;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Service\Cache\CacheServiceImp;
use Wideti\DomainBundle\Service\Configuration\ConfigurationAware;
use Wideti\DomainBundle\Service\Configuration\ConfigurationService;
use Wideti\DomainBundle\Service\Guest\GuestServiceAware;
use Wideti\DomainBundle\Service\GuestNotification\Base\EmailNotificationInterface;
use Wideti\DomainBundle\Service\GuestNotification\Base\SMSNotificationInterface;
use Wideti\DomainBundle\Service\GuestNotification\Senders\EmailServiceAware;
use Wideti\DomainBundle\Service\GuestNotification\Senders\SmsServiceAware;
use Wideti\DomainBundle\Service\Sms\Dto\SmsDto;
use Wideti\DomainBundle\Service\Sms\Dto\SmsBuilder;
use Wideti\FrontendBundle\Factory\Nas;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;

class RegisterNotification implements EmailNotificationInterface, SMSNotificationInterface
{
    use EntityManagerAware;
    use MongoAware;
    use EmailServiceAware;
    use SmsServiceAware;
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
        $client = $this->session->get('wspotClient');
        $guest  = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneBy([
                'id' => $params['guestId']
            ]);

        if ($guest->getStatus() == Guest::STATUS_ACTIVE) {
            $email = isset($guest->getProperties()['email']) ? $guest->getProperties()['email'] : null;
            if (($this->configurationService->get($nas, $client, 'enable_welcome_email') == 1) && $this->guestService->hasEmailFieldInProperties($guest) && !empty($email)) {
                $this->emailService->welcome($guest, $nas, $params);
            }
            if ($this->configurationService->get($nas, $client, 'enable_welcome_sms') == 1) {
                $this->sendSMS($nas, $params);
            }
        }
    }

    public function sendSMS(Nas $nas = null, $params = [])
    {
        $client = $this->session->get('wspotClient');

        $guest = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneBy([
                'id' => $params['guestId']
            ]);

        if ($this->configurationService->get($nas, $client, 'enable_welcome_sms') == 1) {
            $message = $this->configurationService->get($nas, $client, 'content_welcome_sms_pt');

            if ($params['locale'] == 'en') {
                $message = $this->configurationService->get($nas, $client, 'content_welcome_sms_en');
            }

            if ($params['locale'] == 'es') {
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
}
