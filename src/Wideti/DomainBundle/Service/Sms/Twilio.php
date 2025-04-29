<?php

namespace Wideti\DomainBundle\Service\Sms;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Guests;
use Wideti\DomainBundle\Entity\SmsHistoric;
use Wideti\DomainBundle\Exception\GuestNotFoundException;
use Wideti\DomainBundle\Exception\InvalidSmsPhoneNumberException;
use Wideti\DomainBundle\Service\Sms\Dto\SmsDto;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class Twilio implements SmsSenderInterface
{
    use EntityManagerAware;
    use MongoAware;
    use SessionAware;
    use TranslatorAware;

    /**
     * @var Guest
     */
    protected $guest;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $accountId;

    /**
     * @var string
     */
    protected $number;

    /**
     * @var string
     */
    protected $token;

    /**
     * Set twilio account id
     *
     * @param string $accountId
     */
    public function setAccountId($accountId)
    {
        $this->accountId = $accountId;
    }

    /**
     * Set twilio phone number
     *
     * @param string $number
     */
    public function setNumber($number)
    {
        $this->number = "+" . $number;
    }

    /**
     * Set twilio token
     *
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Return api endpoint
     *
     * @return string api url
     */
    public function getUrl()
    {
        return "https://api.twilio.com/2010-04-01/Accounts/{$this->accountId}/Messages.json";
    }

    /**
     * @param SmsDto $sms
     * @param Guest $guest
     * @param $phoneNumber
     * @throws GuestNotFoundException
     * @throws InvalidSmsPhoneNumberException
     */
    public function send(SmsDto $sms, Guest $guest, $phoneNumber)
    {
        /**
         * @var Guests $guestMySql
         */
        $guestMySql = $this->em
            ->getRepository('DomainBundle:Guests')
            ->findOneById($guest->getMysql())
        ;

        if (!$guestMySql) {
            throw new GuestNotFoundException("GuestNotFoundException on Twilio:send()");
        }

        $this->client   = $guestMySql->getClient();
        $this->guest    = $guest;
        $to             = preg_replace('/[^0-9+]/', null, $phoneNumber);

        if (substr($to, 0, 1) != '+') {
            $to = '+' . $to;
        }

        $message = $sms->getContent();

        if ($sms->getType() == SmsDto::WELCOME) {
            $message = str_replace(
                '{{ user }}',
                $guest->getProperties()[$guest->getLoginField()],
                str_replace(
                    '{{ password }}',
                    $guest->getPassword(),
                    $message
                )
            );
        }

        $data = [
            'From' => $this->number,
            'To'   => $to,
            'Body' => $message
        ];

        $post = http_build_query($data);
        $curl = curl_init($this->getUrl());

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, "{$this->accountId}:{$this->token}");
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);

        $jsonReturn = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($jsonReturn, true);

        $errorCodes = [
            21614,
            21211
        ];

        if (isset($response['code']) && in_array($response['code'], $errorCodes)) {
            throw new InvalidSmsPhoneNumberException(
                $this->translator->trans('wspot.change_user_data.invalid_phone')
            );
        }

        $this->saveHistory($response, $sms);
    }

    public function saveHistory($response, SmsDto $sms)
    {
        $guestMySql = $this->em
            ->getRepository('DomainBundle:Guests')
            ->findOneBy([
                'id' => $this->guest->getMysql()
            ])
        ;

        $guestMongo = $this->mongo
            ->getRepository('DomainBundle:Guest\Guest')
            ->findOneBy([
                'mysql' => $this->guest->getMysql()
            ]);

        $message = $sms->getContent();

        if ($sms->getType() == SmsDto::WELCOME) {
            $message = str_replace(
                '{{ user }}',
                $this->guest->getProperties()[$this->guest->getLoginField()],
                str_replace(
                    '{{ password }}',
                    "******",
                    $message
                )
            );
        }

        $client = $guestMySql->getClient();

        $smsHistoric = new SmsHistoric();
        $smsHistoric->setSender(SmsHistoric::SENDER_TWILIO);
        $smsHistoric->setGuest($guestMySql);
        $smsHistoric->setMessageStatus($response['status']);
        $smsHistoric->setMessageId($response['sid']);
        $smsHistoric->setBodyMessage($message);
        $smsHistoric->setSmsCost($this->client->getSmsCost());
        $smsHistoric->setSentTo($response['to']);
        $smsHistoric->setAccessPoint($guestMongo->getRegistrationMacAddress());
        $smsHistoric->setClient($client->getId());

        $this->em->persist($smsHistoric);
        $this->em->flush();
    }
}
