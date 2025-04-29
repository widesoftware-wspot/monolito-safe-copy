<?php

namespace Wideti\DomainBundle\Service\Analytics\Dto;

class EventBuilder
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
	 * @param $clientDomain
	 * @return $this
	 */
	public function withClientDomain($clientDomain)
	{
		$this->clientDomain = $clientDomain;
		return $this;
	}

	/**
	 * @param $clientSegment
	 * @return $this
	 */
	public function withClientSegment($clientSegment)
	{
		$this->clientSegment = $clientSegment;
		return $this;
	}

	/**
	 * @param $userName
	 * @return $this
	 */
	public function withUserName($userName)
	{
		$this->userName = $userName;
		return $this;
	}

	/**
	 * @param $userEmail
	 * @return $this
	 */
	public function withUserEmail($userEmail)
	{
		$this->userEmail = $userEmail;
		return $this;
	}

	/**
	 * @param $userRole
	 * @return $this
	 */
	public function withUserRole($userRole)
	{
		$this->userRole = $userRole;
		return $this;
	}

	/**
	 * @param $category
	 * @return $this
	 */
	public function withCategory($category)
	{
		$this->category = $category;
		return $this;
	}

	/**
	 * @param $name
	 * @return $this
	 */
	public function withName($name)
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @param $eventProperties
	 * @return $this
	 */
	public function withEventProperties($eventProperties)
	{
		$this->eventProperties = $eventProperties;
		return $this;
	}

	public function withSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

	/**
	 * @return EventDto
	 */
	public function build() {
		$response = new EventDto();
		$response->setClientDomain($this->clientDomain);
		$response->setClientSegment($this->clientSegment);
		$response->setUserName($this->userName);
		$response->setUserEmail($this->userEmail);
		$response->setUserRole($this->userRole);
		$response->setCategory($this->category);
		$response->setName($this->name);
		$response->setEventProperties($this->eventProperties);
		$response->setSessionId($this->sessionId);
		return $response;
	}
}
