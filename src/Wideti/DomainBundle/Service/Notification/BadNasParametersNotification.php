<?php

namespace Wideti\DomainBundle\Service\Notification;

use Monolog\Logger;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Service\Notification\Dto\Message;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Twig\TwigEngine;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Service\Mail\MailHeaderService;
use Wideti\DomainBundle\Service\Mailer\MailerService;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessageBuilder;

class BadNasParametersNotification implements NotificationService
{
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
    /**
     * @var EntityManager
     */
    private $em;
	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * BadNasParametersNotification constructor.
	 * @param TwigEngine $twig
	 * @param MailerService $mailer
	 * @param MailHeaderService $mailHeader
	 * @param EntityManager $em
	 * @param Logger $logger
	 */
	public function __construct(
        TwigEngine $twig,
        MailerService $mailer,
        MailHeaderService $mailHeader,
        EntityManager $em,
		Logger $logger
    ) {
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->mailHeader = $mailHeader;
        $this->em = $em;
		$this->logger = $logger;
	}

    public function notify(Client $client, Message $message)
    {
	    $this->logger->addWarning(
		    'Ponto de acesso mal configurado',
		    [
			    'message'   => 'Ponto de acesso mal configurado',
			    'client'    => [
				    'id'     => $client->getId(),
				    'domain' => $client->getDomain()
			    ],
			    'identifier' => $message->getMessage()
		    ]
	    );

//        foreach ($emails as $email) {
//            $builder = new MailMessageBuilder();
//            $mailMessage = $builder
//                ->subject("Ponto de acesso mal configurado - {$client->getDomain()}")
//                ->from(['WSpot' => $this->mailHeader->getSender()])
//                ->to([[$email]])
//                ->htmlMessage(
//                    $this->twig->render(
//                        '@Frontend/Nas/mailBadNasParametersNotification.html.twig',
//                        [
//                            'client'    => $client,
//                            'user'      => [
//                                'name' => 'Developers WSpot'
//                            ],
//                            'message'   => $message->getMessage()
//                        ]
//                    )
//                )
//                ->build();
//            $this->mailer->send($mailMessage);
//        }
    }

    public function getEmails(Client $client)
    {
        $users = $this->em
            ->getRepository('DomainBundle:Users')
            ->findBy(
                [
                    'status' => Users::ACTIVE,
                    'role' => Users::ROLE_ADMIN,
                    'client' => $client
                ]
            );

        $emails = [];

        foreach ($users as $user) {
            if ($user->getUsername() === Users::USER_DEFAULT) {
                continue;
            }
            $emails[] = [
                'name' => $user->getNome(),
                'email' => $user->getUsername()
            ];
        }

        return $emails;
    }
}
