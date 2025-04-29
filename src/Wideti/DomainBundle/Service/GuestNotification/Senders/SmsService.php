<?php

namespace Wideti\DomainBundle\Service\GuestNotification\Senders;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Exception\InvalidSmsPhoneNumberException;
use Wideti\DomainBundle\Service\Mail\MailHeaderServiceAware;
use Wideti\DomainBundle\Service\Mailer\MailerServiceAware;
use Wideti\DomainBundle\Service\Notification\Dto\Message;
use Wideti\DomainBundle\Service\Notification\SmsLimitReachedNotification;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;
use Wideti\DomainBundle\Service\Sms;
use Wideti\DomainBundle\Service\Sms\SmsSenderInterface;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;
use Wideti\DomainBundle\Service\Sms\Dto\SmsDto;

class SmsService
{
    use EntityManagerAware;
    use MongoAware;
    use TranslatorAware;
    use SessionAware;
    use MailerServiceAware;
    use MailHeaderServiceAware;
    use LoggerAware;

    const BRAZIL_DIAL_CODE = "55";
    const SEND_SMS_FAIL = "SMSSendFail";
    const SEND_SMS_FAIL_VALUE = "fail";
    const SEND_SMS_SUCCESS = "SMSSendOK";
    const SEND_SMS_SUCCESS_VALUE = "success";

    protected $url;
    protected $message;

    /**
     * @var SmsSenderInterface
     */
    protected $twilio;
    /**
     * @var SmsSenderInterface
     */
    protected $wavy;

    protected $maxNumberSmsSendingPoc;
    /**
     * @var SmsLimitReachedNotification
     */
    private $limitReachedNotification;
    /**
     * @var Sms\SmsGatewayService
     */
    private $gatewayService;

    /**
     * SmsService constructor.
     * @param $maxNumberSmsSendingPoc
     * @param SmsLimitReachedNotification $limitReachedNotification
     * @param Sms\SmsGatewayService $gatewayService
     */
    public function __construct(
        $maxNumberSmsSendingPoc,
        SmsLimitReachedNotification $limitReachedNotification,
        Sms\SmsGatewayService $gatewayService
    ) {
        $this->maxNumberSmsSendingPoc   = $maxNumberSmsSendingPoc;
        $this->limitReachedNotification = $limitReachedNotification;
        $this->gatewayService = $gatewayService;
    }

    public function setTwilio(SmsSenderInterface $twilio)
    {
        $this->twilio = $twilio;
    }

    public function setWavy(SmsSenderInterface $wavy)
    {
        $this->wavy = $wavy;
    }

    public function send(SmsDto $sms, Guest $guest)
    {
        $client = $this->getLoggedClient();
        $phoneNumber     = null;
        $dialCode        = null;
        $guestProperties = $guest->getProperties();
        $dialCodePhone = $dialCode = isset($guestProperties['dialCodeMobile']) ? $guestProperties['dialCodeMobile'] : "55";
        $dialCodeMobile = $dialCode = isset($guestProperties['dialCodeMobile']) ? $guestProperties['dialCodeMobile'] : "55";

        if (array_key_exists('phone', $guestProperties)) {
            $phoneNumber = $guestProperties['phone'];
            $dialCode = $dialCodePhone;
        }

        if (array_key_exists('mobile', $guestProperties)) {
            $phoneNumber = $guestProperties['mobile'];
            $dialCode = $dialCodeMobile;
        }

        $oauthClientId = $this->session->get('oauthClientId');
        if ($oauthClientId) {
            $oauthRequires2FA = $this->em->getRepository("DomainBundle:OAuthLogin")->findOneBy([
                'domain' => $client->getDomain(),
                'clientId' => $oauthClientId,
                'twoFactorRequired' => true,
            ]);
            if ($oauthRequires2FA){
                $guestData = $this->session->get('guest', []);
                $oauthData = isset($guestData['oauth_data']) ? $guestData['oauth_data'] : null;
                if (isset($oauthData['phone'])) {
                    $dialCode = $dialCodePhone;
                    $phoneNumber = $oauthData['phone'];
                } elseif (isset($oauthData['mobile'])) {
                    $dialCode = $dialCodeMobile;
                    $phoneNumber = $oauthData['mobile'];
                }
            }
        }

        if (!$this->checkLimitSendSms()) {
            $guest = $this->mongo
                ->getRepository('DomainBundle:Guest\Guest')
                ->findOneBy([
                    'id' => $guest->getId()
                ]);

            $phoneNumber = "{$dialCode}{$phoneNumber}";
            $number = preg_replace('/[^0-9+]/', null, $phoneNumber);

            try {
                $this->sendByActiveGateway($sms, $guest, $dialCode, $number);
            } catch (InvalidSmsPhoneNumberException $ex) {
                $this->logger->addError($ex->getMessage());
                $this->session->set(self::SEND_SMS_FAIL, self::SEND_SMS_FAIL_VALUE);
            }
        }
    }

    private function sendByActiveGateway(SmsDto $sms, $guest, $dialCode, $number)
    {
        $activeGateway = $this->gatewayService->activeGateway();
        $service = (in_array($dialCode, [self::BRAZIL_DIAL_CODE])) ? $this->$activeGateway : $this->twilio;
        $service->send($sms, $guest, $number);
        $this->session->remove(self::SEND_SMS_FAIL);
    }

    public function checkLimitSendSms($sendEmail = false)
    {
        $client = $this->getLoggedClient();

        if ($client->getStatus() == Client::STATUS_POC) {
            $totalSend = $this->em
                ->getRepository('DomainBundle:SmsHistoric')
                ->getSmsBillingByClient($client)
            ;

            if (count($totalSend) == ($this->maxNumberSmsSendingPoc-1)) {
                if ($sendEmail) {
                    $message = new Message(Message::WARNING, null);
                    $this->limitReachedNotification->notify($client, $message);
                }
                return false;
            }

            if (count($totalSend) >= $this->maxNumberSmsSendingPoc) {
                return true;
            }
        }

        return false;
    }
}
