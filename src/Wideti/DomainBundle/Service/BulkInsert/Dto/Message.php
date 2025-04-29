<?php

namespace Wideti\DomainBundle\Service\BulkInsert\Dto;

class Message
{
    const SUCCESS = 'success';
    const ERROR = 'error';
    const WARNING = 'warning';

    private $message;
    private $status;

    /**
     * Message constructor.
     * @param $message
     * @param $status
     */
    public function __construct($message, $status)
    {
        $this->message = $message;
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }
}
