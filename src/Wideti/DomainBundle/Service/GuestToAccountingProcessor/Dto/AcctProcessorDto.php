<?php

namespace Wideti\DomainBundle\Service\GuestToAccountingProcessor\Dto;

class AcctProcessorDto implements \JsonSerializable
{
    /**
     * @var integer
     */
    private $id;
    /**
     * @var array
     */
    private $content;

    /**
     * AcctProcessorDto constructor.
     * @param int $id
     * @param array $content
     */
    public function __construct($id, array $content)
    {
        $this->id = $id;
        $this->content = $content;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return array
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param array $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getAsArray()
    {
        return [
            'id'        => $this->getId(),
            'content'   => $this->getContent()
        ];
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
            'id'        => $this->id,
            'content'   => $this->content
        ];
    }
}
