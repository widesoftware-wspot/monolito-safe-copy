<?php

namespace Wideti\DomainBundle\Service\EmailConfigNas;

use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\Mail\MailHeaderServiceAware;
use Wideti\DomainBundle\Service\Mailer\MailerServiceAware;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessageBuilder;
use Wideti\DomainBundle\Service\Mikrotik\MikrotikServiceAware;
use Wideti\DomainBundle\Service\Configuration\ConfigurationServiceAware;
use Wideti\FrontendBundle\Factory\Nas\Nas;
use Wideti\WebFrameworkBundle\Aware\TwigAware;

class EmailConfigNasService
{
    use MailerServiceAware;
    use MailHeaderServiceAware;
    use TwigAware;
    use MikrotikServiceAware;
    use ConfigurationServiceAware;

    /**
     * Send a email with all details that How to setup a WSpot in a device. Check it out!
     * @param Nas $nas
     * @param Client $client
     * @param $email
     * @return mixed
     */
    public function sendConfig($client, $userEmail)
    {

        $fromEmail  = $this->emailHeader->getSupportRecipient();
        $body       = $this->renderView('DomainBundle:Email:ApConfiguration.html.twig', array(
            'entity' => $client,
            'fromEmail' => $fromEmail)
        );

        $builder = new MailMessageBuilder();
        $message = $builder
            ->subject('Configure seu equipamento para utilizar o Mambo WiFi')
            ->from(['Suporte WSpot' => $client->getEmailSenderDefault()])
            ->to([
                [$userEmail]
            ])
            ->htmlMessage($body)
            ->build()
        ;

        return $this->mailerService->send($message);
    }
}
