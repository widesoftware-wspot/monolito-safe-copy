<?php

namespace Wideti\DomainBundle\Service\AccessPoints\Dto\Api;

class AccessPointTemplateDto implements \JsonSerializable
{
    private $id;
    private $name;

    /**
     * AccessPointTemplateDto constructor.
     * @param $id
     * @param $name
     */
    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
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
