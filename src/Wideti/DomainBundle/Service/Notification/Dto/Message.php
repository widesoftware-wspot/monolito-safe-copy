<?php

namespace Wideti\DomainBundle\Service\Notification\Dto;

class Message
{
    const INFO      = 'info';
    const WARNING   = 'warning';
    const ERROR     = 'error';

    private $type;
    private $message;

    public function __construct($type, $message)
    {
        $this->type = $type;
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }
}
