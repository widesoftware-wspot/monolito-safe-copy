<?php

namespace Wideti\DomainBundle\Dto;

class OneGuestQueryDto
{
    private $property;
    private $value;
    private $mysql;
    private $id;

    /**
     * @return mixed
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * @param mixed $property
     */
    public function setProperty($property)
    {
        $this->property = $property;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getMysql()
    {
        return $this->mysql;
    }

    /**
     * @param mixed $mysql
     */
    public function setMysql($mysql)
    {
        $this->mysql = $mysql;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}