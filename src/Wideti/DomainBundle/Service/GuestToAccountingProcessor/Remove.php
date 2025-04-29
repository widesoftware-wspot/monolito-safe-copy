<?php

namespace Wideti\DomainBundle\Service\GuestToAccountingProcessor;

use Monolog\Logger;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsService;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Builder\GuestBuilder;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Builder\RequestBuilder;
use Wideti\DomainBundle\Service\GuestToAccountingProcessor\Dto\RequestDto;
use Wideti\DomainBundle\Service\Queue\Message;

class Remove implements GuestToAccountingProcessor
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var CustomFieldsService
     */
    private $customFieldsService;
    /**
     * @var Producer
     */
    private $rabbitmqProducer;

    /**
     * Remove constructor.
     * @param Logger $logger
     * @param CustomFieldsService $customFieldsService
     * @param Producer $rabbitmqProducer
     */
    public function __construct(
        Logger $logger,
        CustomFieldsService $customFieldsService,
        Producer $rabbitmqProducer
    ) {
        $this->logger               = $logger;
        $this->customFieldsService  = $customFieldsService;
        $this->rabbitmqProducer     = $rabbitmqProducer;
    }

    public function process(Client $client, $guest)
    {
        try {
            $builder = new GuestBuilder();
            $guestDto = $builder
                ->withClientId($client->getId())
                ->withId($guest)
                ->build();

            $requestBuilder = new RequestBuilder();
            $objectToSend = $requestBuilder
                ->withOperation(RequestBuilder::REMOVE)
                ->withGuest($guestDto)
                ->build();

            $this->send($objectToSend);
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
}
