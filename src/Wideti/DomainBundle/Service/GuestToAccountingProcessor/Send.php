<?php

namespace Wideti\DomainBundle\Service\GuestToAccountingProcessor;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Wideti\DomainBundle\Document\Guest\Guest;
use Wideti\DomainBundle\Document\Guest\Social;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\DeviceEntry;
use Wideti\DomainBundle\Exception\GuestNotFoundException;
use Wideti\DomainBundle\Helpers\DateTimeHelper;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsService;
use Wideti\DomainBundle\Service\GuestDevices\GuestDevices;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Builder\GuestBuilder;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Builder\RequestBuilder;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Dto\AccessDataDto;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Dto\GuestDto;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Dto\RequestDto;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Dto\SocialDto;
use Wideti\DomainBundle\Service\Queue\Message;

class Send implements GuestToAccountingProcessor
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var CustomFieldsService
     */
    private $customFieldsService;
    /**
     * @var GuestDevices
     */
    private $guestDevices;
    /**
     * @var Producer
     */
    private $rabbitmqProducer;

    /**
     * Send constructor.
     * @param EntityManager $em
     * @param Logger $logger
     * @param CustomFieldsService $customFieldsService
     * @param GuestDevices $guestDevices
     * @param Producer $rabbitmqProducer
     */
    public function __construct(
        EntityManager $em,
        Logger $logger,
        CustomFieldsService $customFieldsService,
        GuestDevices $guestDevices,
        Producer $rabbitmqProducer
    ) {
        $this->em                   = $em;
        $this->logger               = $logger;
        $this->customFieldsService  = $customFieldsService;
        $this->guestDevices         = $guestDevices;
        $this->rabbitmqProducer     = $rabbitmqProducer;
    }

    public function process(Client $client, $guest)
    {   ## Esse processo está servindo uma fila que envia os dados dos visitantes para
        # um tópico do kafka, consumido por um serviço que envia os dados
        # dos visitantes do cliente kopclub para uma api externa

        if ($client->getId() != 13831 || $client->getDomain() != 'kopclub') {
            return false;
        }
        try {
            $guestObject = null;

            $emailIsValidDate   = $this->getDateObject($guest->getEmailIsValidDate());
            $created            = $this->getDateObject($guest->getCreated());
            $lastAccess         = $this->getDateObject($guest->getLastAccess());

            $social             = $this->getSocial($guest);
            $accessData         = $this->getAccessData($guest);
            $properties         = $this->getProperties($guest);

            $builder = new GuestBuilder();
            $guestDto = $builder
                ->withClientId($client->getId())
                ->withId($guest->getMysql())
                ->withMongoId($guest->getId())
                ->withGroup($guest->getGroup())
                ->withStatus($guest->getStatus())
                ->withEmailIsValid($guest->getEmailIsValid())
                ->withEmailIsValidDate($emailIsValidDate)
                ->withRegisterMode($guest->getRegisterMode())
                ->withLocale($guest->getLocale())
                ->withDocumentType($guest->getDocumentType())
                ->withRegistrationMacAddress($guest->getRegistrationMacAddress())
                ->withCreated($created)
                ->withLastAccess($lastAccess)
                ->withTimezone($guest->getTimezone())
                ->withAccessData($accessData)
                ->withSocial($social)
                ->withProperties($properties)
                ->withLoginField($this->getLoginField($guest))
                ->build();

            if (!$this->isEmptyValues($guestDto) && $this->validateValue($guestDto)) {
                $requestBuilder = new RequestBuilder();
                $objectToSend = $requestBuilder
                    ->withOperation(RequestBuilder::PERSIST)
                    ->withGuest($guestDto)
                    ->build();

                $this->send($objectToSend);
            }
        } catch (\Exception $ex) {
            $this->logger->addCritical('Fail to send Guest to Accounting Processor', [
                'message' => $ex->getMessage()
            ]);
        }
    }

    public function send(RequestDto $object)
    {
        $message = new Message();
        $message->setContent(json_encode($object));

        try {
            $this->rabbitmqProducer->getChannel()->queue_declare('wspot-guests', false, true, false, false);
            $this->rabbitmqProducer->getChannel()->exchange_declare('send_guest','direct', false, true, false);
            $this->rabbitmqProducer->getChannel()->queue_bind(
                'wspot-guests',
                'send_guest'
            );
            $this->rabbitmqProducer->setContentType('application/json');

            $this->rabbitmqProducer->publish(
                $message->getContent()
            );
        } catch (\Exception $exception) {
            $this->logger->addCritical(
                "RabbitMQ Message error: {$exception->getMessage()}. \n  With Stack: {$exception->getTraceAsString()}"
            );
        }
    }

    private function getSocial(Guest $guest)
    {
        $social = [];
        /**
         * @var Social $item
         */
        foreach ($guest->getSocial() as $item) {
            $dto = new SocialDto();
            $dto->setId($item->getId());
            $dto->setType($item->getType());
            array_push($social, $dto->jsonSerialize());
        }

        return $social;
    }

    private function getAccessData(Guest $guest)
    {
        $guestMysql = $this->em->getRepository("DomainBundle:Guests")->findOneBy(['id' => $guest->getMysql()]);
        $devices    = $this->guestDevices->getDevices($guestMysql);

        $accessData = [];

        foreach ($devices as $item) {
            $device = $this->adjustObjectToAcctProcessor($item);

            $dto = new AccessDataDto();
            $dto->setOs($device['os']);
            $dto->setPlatform($device['platform']);
            $dto->setMacAddress($device['macAddress']);
            $dto->setAccessDate($device['accessDate']);

            array_push($accessData, $dto->jsonSerialize());
        }

        return $accessData;
    }

    private function getDateObject($dateObject)
    {
        if (!$dateObject) return null;

        if ($dateObject instanceof \MongoDate) {
            $timestamp = $dateObject->sec;
        }

        if ($dateObject instanceof \DateTime) {
            $timestamp = $dateObject->getTimestamp();
        }

        return DateTimeHelper::secondsToMilleseconds($timestamp);
    }

    private function getProperties(Guest $guest)
    {
        $properties = $guest->getProperties();

        if (!$properties) return [];

        $dateFields = [];
        $dateFieldsTemplate = $this->customFieldsService->getTemplateFieldByType('date');
        foreach ($dateFieldsTemplate as $dateField) {
            $dateFields[] = $dateField->getIdentifier();
        }

        foreach ($guest->getProperties() as $key => $value) {
            if ($value == null) {
                $properties[$key] = "";
            }

            if (in_array($key, $dateFields) && gettype($value) != 'string') {
                $properties[$key] = date('Y-m-d', $value->sec);
            }
        }

        return $properties;
    }

    private function getLoginField(Guest $guest)
    {
        if ($guest->getLoginField()) {
            return $guest->getLoginField();
        }
        return $this->customFieldsService->getLoginField()[0]->getIdentifier();
    }

    private function isEmptyValues(GuestDto $guestDto)
    {
        if (
            $guestDto->getClientId() === null ||
            $guestDto->getId() === null ||
            $guestDto->getMongoId() === null ||
            $guestDto->getStatus() === null ||
            $guestDto->getLocale() === null ||
            $guestDto->getCreated() === null ||
            $guestDto->getProperties() === null ||
            empty($guestDto->getProperties())
        ) {
            $this->logger->addCritical('Attempt to send NULL values to Accounting Processor', [
                'guestDto' => $guestDto
            ]);
            return true;
        }
        return false;
    }

    private function validateValue(GuestDto $guestDto)
    {
        if ($guestDto->getRegistrationMacAddress() && gettype($guestDto->getRegistrationMacAddress()) != 'string') {
            $this->logger->addCritical('Attempt to send RegistrationMacAddress as ARRAY to Accounting Processor', [
                'guestDto' => $guestDto
            ]);
            return false;
        }
        return true;
    }

    private function adjustObjectToAcctProcessor(DeviceEntry $device)
    {
        $device = $device->jsonSerialize();
        $device['macAddress'] = $device['mac_address'];
        $device['accessDate'] = DateTimeHelper::secondsToMilleseconds($device['lastAccess']);
        unset($device['client_id']);
        unset($device['guest_id']);
        unset($device['created']);
        unset($device['mac_address']);
        unset($device['lastAccess']);
        return $device;
    }
}
