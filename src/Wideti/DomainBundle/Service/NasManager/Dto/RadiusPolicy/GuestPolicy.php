<?php

namespace Wideti\DomainBundle\Service\NasManager\Dto\RadiusPolicy;

final class GuestPolicy implements \JsonSerializable
{
    private $username;
    private $password;
    private $employee;

    /**
     * GuestPolicy constructor.
     * @param integer $username
     * @param string $password
     * @param $employee
     */
    public function __construct($username, $password, $employee)
    {
        $this->username = $username;
        $this->password = $password;
        $this->employee = $employee;
    }

    /**
     * @return int
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return mixed
     */
    public function getEmployee()
    {
        return $this->employee;
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
        $params = get_object_vars($this);
        $result = [];
        foreach ($params as $param => $value) {
            $result[$param] = $value;
        }
        return $result;
    }
}
