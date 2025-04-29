<?php

namespace Wideti\DomainBundle\Service\Mailer\Message;

class MailMessage
{
    private $htmlMessage;
    private $plainTextMessage;
    private $subject;
    private $from;
    private $to;
    private $replyTo;
    private $attachment;
    private $configurationSet;
    private $identifier;

    /**
     * @return mixed
     */
    public function getHtmlMessage()
    {
        return $this->htmlMessage;
    }

    /**
     * @param mixed $htmlMessage
     */
    public function setHtmlMessage($htmlMessage)
    {
        $this->htmlMessage = $htmlMessage;
    }

    /**
     * @return mixed
     */
    public function getPlainTextMessage()
    {
        return $this->plainTextMessage;
    }

    /**
     * @param mixed $plainTextMessage
     */
    public function setPlainTextMessage($plainTextMessage)
    {
        $this->plainTextMessage = $plainTextMessage;
    }

    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param mixed $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return mixed
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param mixed $from
     */
    public function setFrom($from)
    {
        $fromArray['name']  = (is_int(key($from)) == true ? '' : key($from));
        $fromArray['email'] = $from[key($from)];

        $this->from = $fromArray;
    }

    /**
     * @return mixed
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param mixed $to
     */
    public function setTo($to)
    {
        $toArray = [];

        foreach ($to as $value) {
            array_push($toArray, [
                'email' => $value[key($value)],
                'name'  => (is_int(key($value)) == true ? null : key($value)),
                'type'  => 'to'
            ]);
        }

        $this->to = $toArray;
    }

    /**
     * @return mixed
     */
    public function getReplyTo()
    {
        return $this->replyTo;
    }

    /**
     * @param mixed $replyTo
     */
    public function setReplyTo($replyTo)
    {
        $this->replyTo = $replyTo;
    }

    /**
     * @return mixed
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * @param mixed $attachment
     */
    public function setAttachment($attachment)
    {
        $this->attachment = $attachment;
    }

	/**
	 * @return mixed
	 */
	public function getConfigurationSet()
	{
		return $this->configurationSet;
	}

	/**
	 * @param mixed $configurationSet
	 */
	public function setConfigurationSet($configurationSet)
	{
		$this->configurationSet = $configurationSet;
	}

	/**
	 * @return mixed
	 */
	public function getIdentifier()
	{
		return $this->identifier;
	}

	/**
	 * @param mixed $identifier
	 */
	public function setIdentifier($identifier)
	{
		$this->identifier = $identifier;
	}
}
