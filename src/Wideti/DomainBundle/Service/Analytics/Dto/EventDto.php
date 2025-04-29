<?php

namespace Wideti\DomainBundle\Service\Analytics\Dto;

class EventDto implements \JsonSerializable
{
	public $clientDomain;
	public $clientSegment;

	public $userName;
	public $userEmail;
	public $userRole;

	public $category;
	public $name;
	public $eventProperties;

	public $sessionId;

	/**
	 * @return mixed
	 */
	public function getClientDomain()
	{
		return $this->clientDomain;
	}

	/**
	 * @param mixed $clientDomain
	 */
	public function setClientDomain($clientDomain)
	{
		$this->clientDomain = $clientDomain;
	}

	/**
	 * @return mixed
	 */
	public function getClientSegment()
	{
		return $this->clientSegment;
	}

	/**
	 * @param mixed $clientSegment
	 */
	public function setClientSegment($clientSegment)
	{
		$this->clientSegment = $clientSegment;
	}

	/**
	 * @return mixed
	 */
	public function getUserName()
	{
		return $this->userName;
	}

	/**
	 * @param mixed $userName
	 */
	public function setUserName($userName)
	{
		$this->userName = $userName;
	}

	/**
	 * @return mixed
	 */
	public function getUserEmail()
	{
		return $this->userEmail;
	}

	/**
	 * @param mixed $userEmail
	 */
	public function setUserEmail($userEmail)
	{
		$this->userEmail = $userEmail;
	}

	/**
	 * @return mixed
	 */
	public function getUserRole()
	{
		return $this->userRole;
	}

	/**
	 * @param mixed $userRole
	 */
	public function setUserRole($userRole)
	{
		$this->userRole = $userRole;
	}

	/**
	 * @return mixed
	 */
	public function getCategory()
	{
		return $this->category;
	}

	/**
	 * @param mixed $category
	 */
	public function setCategory($category)
	{
		$this->category = $category;
	}

	/**
	 * @return mixed
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param mixed $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

    /**
     * @return mixed
     */
    public function getEventProperties()
    {
        return $this->eventProperties;
    }

    /**
     * @param mixed $eventProperties
     */
    public function setEventProperties($eventProperties)
    {
        $this->eventProperties = $eventProperties;
    }

    /**
     * @return mixed
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @param mixed $sessionId
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
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
		$vars = get_object_vars($this);
		$result = [];
		foreach ($vars as $key => $value) {
			$result[$key] = $value;
		}
		return $result;
	}
}
