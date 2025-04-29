<?php

namespace Wideti\DomainBundle\Service\Mailer\Providers;

use Wideti\DomainBundle\Service\Mailer\MailReturnStatus;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessage;
use Wideti\DomainBundle\Service\Mailer\Message\ProviderMessageFactory;

class MandrillProvider implements Provider
{
    /**
     * @var \Mandrill
     */
    private $mandrill;

    public function __construct($key)
    {
        $this->mandrill = new \Mandrill($key);
    }

    /**
     * @param MailMessage $message
     * @return MailReturnStatus
     */
    public function send(MailMessage $message)
    {
        $mandrillMessage = ProviderMessageFactory::createMandrillMessage($message);
        $send = $this->mandrill->messages->send($mandrillMessage);

        $returnStatus = new MailReturnStatus();
        $returnStatus->setMessageId($send[0]['_id']);
        $returnStatus->setEmail($send[0]['email']);
        $returnStatus->setStatus($send[0]['status']);
        $returnStatus->setRejectedReason($send[0]['reject_reason']);

        return $returnStatus;
    }
}
