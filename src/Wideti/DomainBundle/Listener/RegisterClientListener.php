<?php

namespace Wideti\DomainBundle\Listener;

use Wideti\DomainBundle\Service\Mail\MailHeaderServiceAware;
use Wideti\DomainBundle\Service\Mailer\MailerServiceAware;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessageBuilder;
use Wideti\DomainBundle\Event\RegisterClientEvent;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class RegisterClientListener
{
    use MailerServiceAware;
    use MailHeaderServiceAware;
    use TwigAware;

    public function register(RegisterClientEvent $event)
    {
        $client     = $event->getClient();
        $mikrotik   = $event->getMikrotik();

        $builder = new MailMessageBuilder();
        $message = $builder
            ->subject('Novo Cliente')
            ->from(['Novo Cliente' => $this->emailHeader->getSender()])
            ->to($this->emailHeader->getAdminRecipient())
            ->htmlMessage(
                $this->renderView(
                    'AdminBundle:Client:emailNewClient.html.twig',
                    array(
                        'client'   => $client,
                        'mikrotik' => $mikrotik
                    )
                )
            )
            ->build()
        ;

        $this->mailerService->send($message);
    }
}
