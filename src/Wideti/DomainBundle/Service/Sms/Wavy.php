<?php

namespace Wideti\DomainBundle\Service\Sms;

use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Guests;
use Wideti\DomainBundle\Entity\SmsHistoric;
use Wideti\DomainBundle\Exception\GuestNotFoundException;
use Wideti\DomainBundle\Service\Sms\Dto\SmsDto;
use Wideti\DomainBundle\Service\Translator\TranslatorAware;
use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\LoggerAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class Wavy implements SmsSenderInterface
{
    const WAVY_SEND_SMS_URL = "https://api-messaging.wavy.global/v1/send-sms";
    const SUCCESS_SENT_STATUS = "SENT_SUCCESS";

    use EntityManagerAware;
    use MongoAware;
    use SessionAware;
    use TranslatorAware;
    use LoggerAware;

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
    protected $token;

    /**
     * @var string
     */
    protected $username;

    /**
     * @param $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @param $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @param SmsDto $sms
     * @param Guest $guest
     * @param $phoneNumber
     * @throws GuestNotFoundException
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
            throw new GuestNotFoundException("GuestNotFoundException on Wavy:send()");
        }

        $this->client   = $guestMySql->getClient();
        $this->guest    = $guest;
        $to             = preg_replace('/[^0-9+]/', null, $phoneNumber);

        $smsId = $this->saveBeforeHistory();

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
        $body = [
            "correlationId" => $smsId,
            "destination"   => $to,
            "messageText"   => $message
        ];

        try {
            $response = $this->curl("POST", self::WAVY_SEND_SMS_URL, $body);
        } catch (\Exception $ex) {
            $this->sendErrorToLog($ex->getMessage());
            throw new \Exception(
                $this->translator->trans('wspot.confirmation.sms_sent_error')
            );
        }

        $sendStatusObject               = new \stdClass();
        $sendStatusObject->messageId    = $response["id"];
        $sendStatusObject->messageBody  = $message;
        $sendStatusObject->client       = $this->getLoggedClient()->getDomain();
        $sendStatusObject->destination  = $to;

        try {
            $this->saveAfterHistory($smsId, $sendStatusObject, $sms);
        } catch (\Exception $ex) {
            $this->logger->addCritical(
                "Wavy - Fail to save SMS History",
                json_decode(json_encode($sendStatusObject), true)
            );
        }
    }

    private function curl($method, $url, $body)
    {
        $curl = curl_init();

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                "authenticationtoken: {$this->token}",
                "username: {$this->username}",
                "content-type: application/json"
            ]
        ];

        if ($method == "POST") {
            $options[CURLOPT_POSTFIELDS] = json_encode($body);
        }

        curl_setopt_array($curl, $options);

        $jsonReturn = curl_exec($curl);
        curl_close($curl);

        return json_decode($jsonReturn, true);
    }

    private function sendErrorToLog($sendStatusObject)
    {
        $this->logger->addCritical(
            "Wavy - Fail to send SMS",
            json_decode(json_encode($sendStatusObject), true)
        );
    }

    private function saveBeforeHistory()
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

        $client = $guestMySql->getClient();

        $smsHistoric = new SmsHistoric();

        $smsHistoric->setSender(SmsHistoric::SENDER_WAVY);
        $smsHistoric->setGuest($guestMySql);
        $smsHistoric->setSmsCost($this->client->getSmsCost());
        $smsHistoric->setAccessPoint($guestMongo->getRegistrationMacAddress());
        $smsHistoric->setClient($client->getId());

        $this->em->persist($smsHistoric);
        $this->em->flush();

        return $smsHistoric->getId();
    }

    private function saveAfterHistory($smsId, $response, SmsDto $sms)
    {
        $smsHistoric = $this->em
            ->getRepository('DomainBundle:SmsHistoric')
            ->findOneBy([
                'id' => $smsId
            ])
        ;

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

        $smsHistoric->setMessageStatus(isset($response->status) ? $response->status : '');
        $smsHistoric->setMessageStatusCode(isset($response->code) ? $response->code : '');
        $smsHistoric->setMessageId($response->messageId);
        $smsHistoric->setBodyMessage($message);
        $smsHistoric->setSentTo($response->destination);

        $this->em->persist($smsHistoric);
        $this->em->flush();
    }

    /**
     * TODO ========================== IMPORTANTE ======================================================================
     * Não estamos usando o saveHistory() da interface e sim usando os outros 2 acima saveBeforeHistory() e
     * saveAfterHistory() pelo seguinte motivo:
     * Ao enviar a mensagem pro Wavy, não temos o retorno se teve sucesso ou falha. A API retorna apenas o ID para que
     * seja utilizado posteriormente para consultar o status, através de outro endpoint.
     * por isso chamamos o saveBeforeHistory() para que salve apenas o ID da mensagem, ID do cliente e ID do visitante.
     * O saveBeforeHistory() apenas salva o conteúdo da mensagem e o ID que a Wavy nos retorna.
     * No Painel da Wavy está configurado o callback para que seja enviado para nossa API: /api/internal/sms-callback.
     * Nesse callback nós salvamos a informação completa do envio e entrega da SMS.
     */
    public function saveHistory($history, SmsDto $sms)
    {
    }
}
