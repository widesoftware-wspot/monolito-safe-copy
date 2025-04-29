<?php

namespace Wideti\DomainBundle\Service\GuestToAccountingProcessor\Dto;

class RequestDto implements \JsonSerializable
{
    /**
     * @var string
     */
    private $operation;
    /**
     * @var string
     */
    private $guest;

    /**
     * RequestDto constructor.
     * @param string $operation
     * @param string $guest
     */
    public function __construct($operation, $guest)
    {
        $this->operation = $operation;
        $this->guest     = $guest;
    }

    /**
     * @return string
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @param string $operation
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;
    }

    /**
     * @return string
     */
    public function getGuest()
    {
        return $this->guest;
    }

    /**
     * @param string $guest
     */
    public function setGuest($guest)
    {
        $this->guest = $guest;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'operation' => $this->operation,
            'guest'     => $this->guest
        ];
    }
}
