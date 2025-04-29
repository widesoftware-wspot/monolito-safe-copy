<?php

namespace Wideti\DomainBundle\Service\Social\Facebook\Dto;


class PublishParametersDto
{
    /** @var string */
    private $guestId;
    /** @var string */
    private $socialType;
    /** @var string */
    private $action;

    /**
     * @return string
     */
    public function getGuestId()
    {
        return $this->guestId;
    }

    /**
     * @param string $guestId
     * @return PublishParametersDto
     */
    public function setGuestId($guestId)
    {
        $this->guestId = $guestId;
        return $this;
    }

    /**
     * @return string
     */
    public function getSocialType()
    {
        return $this->socialType;
    }

    /**
     * @param string $socialType
     * @return PublishParametersDto
     */
    public function setSocialType($socialType)
    {
        $this->socialType = $socialType;
        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     * @return PublishParametersDto
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }
}
