<?php

namespace Wideti\DomainBundle\Service\NasManager\Dto\AuthReport;

use Wideti\DomainBundle\Entity\Client;

class ClientEvent implements \JsonSerializable
{
    /**
     * @var integer
     */
    private $id;

    private function __construct() {}

    public static function createFrom(Client $client)
    {
        $event = new ClientEvent();
        $event->id = $client->getId();
        return $event;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource.
     *
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->id
        ];
    }
}