<?php

namespace Wideti\DomainBundle\Service\Mailer\Message;

class MailMessageBuilder
{
    /**
     * @var MailMessage
     */
    private $message;

    public function __construct()
    {
        $this->message = new MailMessage();
    }

    public function htmlMessage($message)
    {
        $this->message->setHtmlMessage($message);
        return $this;
    }

    public function plainTextMessage($message)
    {
        $this->message->setPlainTextMessage($message);
        return $this;
    }

    public function subject($subject)
    {
        $this->message->setSubject($subject);
        return $this;
    }

    public function from($from)
    {
        $this->message->setFrom($from);
        return $this;
    }

    public function to($to)
    {
        $this->message->setTo($to);
        return $this;
    }

    public function replyTo($replyTo)
    {
        $this->message->setReplyTo($replyTo);
        return $this;
    }

    public function attachment($attachment)
    {
        $this->message->setAttachment($attachment);
        return $this;
    }

    public function tracking($configurationSetName = null)
    {
    	$this->message->setConfigurationSet($configurationSetName);
    	return $this;
    }

    public function identifier($identifier = null)
    {
    	$this->message->setIdentifier($identifier);
    	return $this;
    }

    /**
     * @return MailMessage
     */
    public function build()
    {
        return $this->message;
    }
}
