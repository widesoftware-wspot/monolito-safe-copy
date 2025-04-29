<?php

namespace Wideti\DomainBundle\Service\Sms\Dto;

class SmsDto
{
    const WELCOME = "welcome";
    const CONFIRM_REGISTRATION = "confirm_registration";

    private $type;
    private $content;

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }
}
