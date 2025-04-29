<?php

namespace Wideti\DomainBundle\Service\Mailer;

class MailReturnStatus
{
    private $messageId;
    private $email;
    private $status;
    private $rejectedReason;

    /**
     * @return mixed
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * @param mixed $messageId
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getRejectedReason()
    {
        return $this->rejectedReason;
    }

    /**
     * @param mixed $rejectedReason
     */
    public function setRejectedReason($rejectedReason)
    {
        $this->rejectedReason = $rejectedReason;
    }
}
