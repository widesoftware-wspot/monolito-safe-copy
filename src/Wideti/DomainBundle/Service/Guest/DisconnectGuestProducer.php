<?php

namespace Wideti\DomainBundle\Service\Guest;

use Monolog\Logger;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Wideti\DomainBundle\Service\Queue\Message;
use PhpAmqpLib\Message\AMQPMessage;

class DisconnectGuestProducer
{
    /**
     * @var Producer
     */
    private $rabbitmqProducer;

    /**
     * DisconnectGuestProducer constructor.
     * @param Producer $rabbitmqProducer
     */
    public function __construct(
        Producer $rabbitmqProducer
    ) {
        $this->rabbitmqProducer     = $rabbitmqProducer;
    }

    public function publishRequest($object)
    {
        $objectEncoded = json_encode($object);

        $this->rabbitmqProducer->publish($objectEncoded);
        
        return true;
    }

}
