<?php

namespace Wideti\DomainBundle\Document\CustomFields;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(
 *      collection="fields",
 *      repositoryClass="Wideti\DomainBundle\Document\Repository\Fields\FieldRepository"
 * )
 */
class Field implements \JsonSerializable
{
    /**
     * @ODM\Id()
     */
    protected $id;

    /**
     * @ODM\Field(type="string")
     */
    protected $type;

    /**
     * @ODM\Field(type="hash")
     */
    protected $name;

    /**
     * @ODM\Field(type="string")
     */
    protected $identifier;

    /**
     * @ODM\Field(type="hash")
     */
    protected $choices;

    /**
     * @ODM\Field(type="hash")
     */
    protected $validations;

    /**
     * @ODM\Field(type="hash")
     */
    protected $mask;

    /**
     * @ODM\Field(type="boolean")
     */
    protected $isUnique;

    /**
     * @ODM\Field(type="boolean")
     */
    protected $isLogin;

    /**
     * @ODM\Field(type="integer")
     */
    protected $position;

    /**

     * @ODM\Field(type="collection")
     */
    protected $groupId;

    /**
     * @ODM\Field(type="integer")
     */
    protected $onAccess;

    /**
     * Field constructor.
     */
    public function __construct()
    {
        $this->choices = [];
        $this->groupId = [];
    }

    /**
     * @param $param
     * @return mixed
     */
    public function __get($param)
    {
        return $this->$param;
    }

    /**
     * @param $param
     * @param $value
     */
    public function __set($param, $value)
    {
        $this->$param = $value;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param $identifier
     * @return $this
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $language
     * @param $name
     * @return $this
     */
    public function addName($language, $name)
    {
        $this->name[$language] = $name;
        return $this;
    }

    /**
     * @param array $name
     * @return $this
     */
    public function setNames(array $name = [])
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getNames()
    {
        return $this->name;
    }

    /**
     * @param $locale
     * @return mixed
     */
    public function getNameByLocale($locale)
    {
        if ($locale == null) {
            return $this->name["pt_br"];
        }

        return $this->name[$locale];
    }

    /**
     * @param array $choices
     * @return $this
     */
    public function setChoices(array $choices)
    {
        $this->choices = $choices;
        return $this;
    }

    /**
     * @return array
     */
    public function getChoices()
    {
        return $this->choices;
    }

    /**
     * @return mixed
     */
    public function getValidations()
    {
        return $this->validations;
    }

    /**
     * @param $validations
     * @return $this
     */
    public function setValidations($validations)
    {
        $this->validations = $validations;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMask()
    {
        return $this->mask;
    }

    /**
     * @param $mask
     * @return $this
     */
    public function setMask($mask)
    {
        $this->mask = $mask;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsUnique()
    {
        return $this->isUnique;
    }

    /**
     * @param $isUnique
     * @return $this
     */
    public function setIsUnique($isUnique)
    {
        $this->isUnique = $isUnique;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsLogin()
    {
        return $this->isLogin;
    }

    /**
     * @param $isLogin
     * @return $this
     */
    public function setIsLogin($isLogin)
    {
        $this->isLogin = $isLogin;
        return $this;
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
        return get_object_vars($this);
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param $position
     * @return $this
     */
    public function setPosition($position)
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @param mixed $groupId
     * @return $this
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
        return $this;
    }
    /**
     * @return integer
     */
    public function getOnAccess()
    {
        return $this->onAccess;
    }

    /**
     * @param string $onAccess
     * @return $this
     */
    public function setOnAccess($onAccess)
    {
        $this->onAccess = (integer) $onAccess;
        return $this;
    }
}
