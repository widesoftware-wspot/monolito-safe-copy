<?php

namespace Wideti\DomainBundle\Service\Notification;

use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Twig\TwigEngine;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Service\Mail\MailHeaderService;
use Wideti\DomainBundle\Service\Mailer\MailerService;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessageBuilder;
use Wideti\DomainBundle\Service\Notification\Dto\Message;

class SmsLimitReachedNotification implements NotificationService
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var TwigEngine
     */
    private $twig;
    /**
     * @var MailerService
     */
    private $mailer;
    /**
     * @var MailHeaderService
     */
    private $mailHeader;

    public function __construct(
        EntityManager $em,
        TwigEngine $twig,
        MailerService $mailer,
        MailHeaderService $mailHeader
    ) {
        $this->em           = $em;
        $this->twig         = $twig;
        $this->mailer       = $mailer;
        $this->mailHeader   = $mailHeader;
    }

    public function notify(Client $client, Message $message)
    {
        $financialEmail = $this->mailHeader->getFinancialRecipient()[0][0];

        foreach ($this->getEmails($client) as $userEmail) {
            $builder = new MailMessageBuilder();
            $message = $builder
                ->subject('Envio de SMS - Limite de quantidade atingido')
                ->from(['WSpot' => $this->mailHeader->getSender()])
                ->to([
                    [$userEmail]
                ])
                ->htmlMessage(
                    $this->twig->render(
                        'AdminBundle:SmsHistoric:limitReached.html.twig',
                        [
                            'user'           => $userEmail,
                            'client'         => $client,
                            'financialEmail' => $financialEmail
                        ]
                    )
                )
                ->build()
            ;

            $this->mailer->send($message);
        }
    }

    public function getEmails($client)
    {
        $users = $this->em
            ->getRepository('DomainBundle:Users')
            ->findBy(
                [
                    'status'    => Users::ACTIVE,
                    'role'      => Users::ROLE_ADMIN,
                    'client'    => $client
                ]
            )
        ;


        $usersLimited = $this->em
            ->getRepository('DomainBundle:Users')
            ->findBy(
                [
                    'status'    => Users::ACTIVE,
                    'role'      => Users::ROLE_ADMIN_LIMITED,
                    'client'    => $client
                ]
            )
        ;
        $users = array_merge($users, $usersLimited);
        $emails = [];
        foreach ($users as $user) {
            if ($user->getUsername() === Users::USER_DEFAULT) {
                continue;
            }
            $emails[] = $user->getUsername();
        }

        return $emails;
    }
}
