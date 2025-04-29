<?php

namespace Wideti\DomainBundle\Service\BulkInsert\Dto;

class BulkResponse
{
    const ERROR = 'error';
    const SUCCESS = 'success';
    const WARNING = 'warning';

    /**
     * @var Message[]
     */
    private $messages;

    public function __construct()
    {
        $this->messages = [];
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        $warningCount = 0;
        $errorCount = 0;
        foreach ($this->messages as $message) {
            if ($message->getStatus() === Message::WARNING) {
                $warningCount++;
            }

            if ($message->getStatus() === Message::ERROR) {
                $errorCount++;
            }
        }

        if ($errorCount > 0) {
            return self::ERROR;
        }

        if ($warningCount > 0) {
            return self::WARNING;
        }

        return self::SUCCESS;
    }

    /**
     * @return int
     */
    public function getErrorTotal()
    {
        $count = 0;
        foreach ($this->messages as $message) {
            if ($message->getStatus() === Message::ERROR) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * @return int
     */
    public function getWarningTotal()
    {
        $count = 0;
        foreach ($this->messages as $message) {
            if ($message->getStatus() === Message::WARNING) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * @return int
     */
    public function getSuccessTotal()
    {
        $count = 0;
        foreach ($this->messages as $message) {
            if ($message->getStatus() === Message::SUCCESS) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * @return Message[]
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param Message[] $messages
     */
    public function setMessages($messages)
    {
        $this->messages = $messages;
    }

    public function addMessage($message, $status)
    {
        array_push($this->messages, new Message($message, $status));
    }
}
