<?php

namespace Wideti\DomainBundle\Service\Notification;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bridge\Twig\TwigEngine;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Service\Mail\MailHeaderService;
use Wideti\DomainBundle\Service\Mailer\MailerService;
use Wideti\DomainBundle\Service\Mailer\Message\MailMessageBuilder;
use Wideti\DomainBundle\Service\Notification\Dto\Message;

class ApNotRegisteredNotification implements NotificationService
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
	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * ApNotRegisteredNotification constructor.
	 * @param EntityManager $em
	 * @param TwigEngine $twig
	 * @param MailerService $mailer
	 * @param MailHeaderService $mailHeader
	 * @param Logger $logger
	 */
	public function __construct(
        EntityManager $em,
        TwigEngine $twig,
        MailerService $mailer,
        MailHeaderService $mailHeader,
		Logger $logger
    ) {
        $this->em = $em;
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->mailHeader = $mailHeader;
		$this->logger = $logger;
	}

    public function notify(Client $client, Message $message)
    {
    	$this->logger->addWarning(
		    'Ponto de acesso mal configurado',
	        [
	        	'message'   => 'Tentativa de acesso em ponto de acesso não cadastrado',
				'client'    => [
					'id'     => $client->getId(),
					'domain' => $client->getDomain()
				],
		        'identifier' => $message->getMessage()
	        ]
	    );

//        foreach ($this->getEmails($client) as $user) {
//            $builder = new MailMessageBuilder();
//            $mailMessage = $builder
//                ->subject('Tentativa de acesso em ponto de acesso não cadastrado')
//                ->from(['WSpot' => $this->mailHeader->getSender()])
//                ->to([ [$user['email']] ])
//                ->htmlMessage(
//                    $this->twig->render(
//                        'FrontendBundle:Nas:mailNotification.html.twig',
//                        [
//                            'client'        => $client,
//                            'user'          => $user,
//                            'identifier'    => $message->getMessage()
//                        ]
//                    )
//                )
//                ->build()
//            ;
//
//            $this->mailer->send($mailMessage);
//        }
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

        $emails = [];

        foreach ($users as $user) {
            if ($user->getUsername() === Users::USER_DEFAULT) {
                continue;
            }
            $emails[] = [
                'name'  => $user->getNome(),
                'email' => $user->getUsername()
            ];
        }

        return $emails;
    }
}
