<?php

namespace Wideti\DomainBundle\Service\Mailer\Providers;

use Wideti\DomainBundle\Service\Mailer\MailReturnStatus;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessage;
use Wideti\DomainBundle\Service\Mailer\Message\ProviderMessageFactory;
use Wideti\WebFrameworkBundle\Aware\MailerAware;

class SwiftMailerProvider implements Provider
{
    use MailerAware;

    /**
     * @param MailMessage $message
     * @return MailReturnStatus
     */
    public function send(MailMessage $message)
    {
        $content = ProviderMessageFactory::createSwiftMailerMessage($message);

        $message = \Swift_Message::newInstance()
            ->setSubject($content['subject'])
            ->setFrom(array($content['from_email'] => $content['from_name']))
            ->setTo($content['to'][0]['email'])
            ->setContentType('text/html')
            ->setBody($content['html'])
        ;

        $this->mailer->send($message);
    }
}
