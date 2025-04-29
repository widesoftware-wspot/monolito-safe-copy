<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$em             = $container->get('doctrine.orm.entity_manager');
$monolog        = $container->get('logger');
$twig           = $container->get('templating');
$mailer         = $container->get('core.service.mailer');
$emailHeader    = $container->get('core.service.email_header');
$whiteLabel     = $container->get('core.service.white_label');

$clients = $em->getRepository('DomainBundle:Client')
    ->getClientsTestingExpiringThisWeek();

$expireds = $em->getRepository('DomainBundle:Client')
    ->getClientsTestingExpireds();

$html = $twig->render(
    'DomainBundle:MailReport:emailClientesPocSemana.html.twig',
    [
        'entities'      => $clients,
        'expireds'      => $expireds,
        'whiteLabel'    => $whiteLabel->getDefaultWhiteLabel()
    ]
);

$builder = new \Wideti\DomainBundle\Service\Mailer\Message\MailMessageBuilder();
$message = $builder
    ->subject('Clientes encerramento a POC')
    ->from(['WSpot' => $emailHeader->getSender()])
    ->to($emailHeader->getCommercialRecipient())
    ->htmlMessage($html)
    ->build()
;

$mailer->send($message);
