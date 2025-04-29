<?php

namespace Wideti\DomainBundle\Service\Segmentation\Dto;

use Symfony\Component\HttpFoundation\Request;

class ExportDto
{
    public $client;
    public $segmentationId;
    public $recipient;

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param $client
     * @return $this
     */
    public function setClient($client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSegmentationId()
    {
        return $this->segmentationId;
    }

    /**
     * @param $segmentationId
     * @return $this
     */
    public function setSegmentationId($segmentationId)
    {
        $this->segmentationId = $segmentationId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * @param $recipient
     * @return $this
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;
        return $this;
    }

    public static function createFromRequest(Request $request)
    {
        $content = json_decode($request->getContent(), true);

        $dto = new ExportDto();
        $dto->setClient($content['client']);
        $dto->setSegmentationId($content['segmentationId']);
        $dto->setRecipient($content['recipient']);

        return $dto;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $properties = [];
        $fields = get_class_vars(get_class($this));

        foreach ($fields as $key => $value) {
            $objPropertiesValues = get_object_vars($this);
            $properties[$key] = $objPropertiesValues[$key];
        }

        return $properties;
    }
}
