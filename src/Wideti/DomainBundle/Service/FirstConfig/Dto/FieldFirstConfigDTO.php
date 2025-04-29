<?php

namespace Wideti\DomainBundle\Service\FirstConfig\Dto;

class FieldFirstConfigDTO implements \JsonSerializable
{
    /**
     * @var string
     */
    private $identifier;
    /**
     * @var boolean
     */
    private $login;
    /**
     * @var boolean
     */
    private $unique;
    /**
     * @var boolean
     */
    private $required;

    /**
     * @var string
     */
    private $label;

    public function __set($key, $value)
    {
        $this->$key = $value;
    }

    public function __get($key)
    {
        return $this->$key;
    }

    public static function buildFromArray(array $field)
    {
        $fieldDto = new FieldFirstConfigDTO();
        foreach ($field as $key => $value) {
            $fieldDto->__set($key, $value);
        }
        return $fieldDto;
    }


    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return bool
     */
    public function isLogin()
    {
        return $this->login;
    }

    /**
     * @param bool $login
     */
    public function setLogin($login)
    {
        $this->login = $login;
    }

    /**
     * @return bool
     */
    public function isUnique()
    {
        return $this->unique;
    }

    /**
     * @param bool $unique
     */
    public function setUnique($unique)
    {
        $this->unique = $unique;
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @param bool $required
     */
    public function setRequired($required)
    {
        $this->required = $required;
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
        $properties = [];
        $fields = get_class_vars(get_class($this));

        foreach ($fields as $key => $value) {
            $objPropertiesValues = get_object_vars($this);
            $properties[$key] = $objPropertiesValues[$key];
        }
        return $properties;
    }
}
