<?php

namespace Wideti\DomainBundle\Service\FirstConfig\Dto;

class FirstConfigResponse implements \JsonSerializable
{
    /**
     * @var string
     */
    private $message;

    /**
     * FirstConfigResponse constructor.
     * @param $message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }


    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize()
    {
      return get_object_vars($this);
    }
}