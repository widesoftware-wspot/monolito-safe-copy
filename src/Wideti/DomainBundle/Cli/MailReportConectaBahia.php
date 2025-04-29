<?php

require_once __DIR__ . '/../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Wideti\DomainBundle\Entity\Client;

$kernel = new AppKernel('prod', true);
$kernel->boot();
$output         = new \Symfony\Component\Console\Output\ConsoleOutput();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$em         = $container->get('doctrine.orm.entity_manager');
$mongo      = $container->get('doctrine.odm.mongodb.document_manager');
$monolog    = $container->get('logger');
$reportMail = $application->getKernel()->getContainer()->get('core.service.mail_report');
$analyzer   = $container->get('core.service.analytics');

$builder = new \Wideti\DomainBundle\Service\Analytics\Dto\EventBuilder();
$event = $builder
    ->withClientDomain('wideti')
    ->withClientSegment('')
    ->withUserName('Resumo Semanal')
    ->withUserEmail('no-reply@wspot.com.br')
    ->withUserRole('SUPER_ADMIN')
    ->withCategory('')
    ->withName('Resumo_Mensal_Enviou')
    ->withEventProperties([
        'date' => date('Y-m-d')
    ])
    ->build();

$output->writeln("-----------------------------");
$output->writeln("Carregando a base do cliente");
$output->writeln("-----------------------------");
$clients = $em->getRepository('DomainBundle:Client')
    ->findByDomain("conectabahia");


$output->writeln("Base de clientes carregada");
$output->writeln("-----------------------------");

foreach ($clients as $client) {
    if ($client->getReportSent()) continue;

    $db = \Wideti\DomainBundle\Helpers\StringHelper::slugDomain($client->getDomain());
    $mongo
        ->getConfiguration()
        ->setDefaultDB($db)
    ;
    $output->writeln("Enviando email para o cliente ". $client->getDomain());

    $newMongo = $mongo->create(
        $mongo->getConnection(),
        $mongo->getConfiguration(),
        $mongo->getEventManager()
    );

    $reportMail->setMongo($newMongo);
    try {
        $now            = new \DateTime();
        $lastDayLastMonth = date('t', strtotime('last day of previous month'));
        $reportMail->init($client, intval($lastDayLastMonth));
        $analyzer->sendEvent($event);
    } catch (Exception $e) {
        $monolog->addCritical("[Weekly mail] Fail to send email to client {$db}",
            [
                "message" => $e->getMessage(),
                'error' => $e->getTraceAsString()
            ]
        );
        continue;
    }
    usleep(50000);

}

$output->writeln("Emails semanais eviados com sucesso".PHP_EOL."Preparando para redefinir o status de envio na base");
$em->getRepository('DomainBundle:Client')
    ->updateAllClientsToReportSentEqualFalse();
$output->writeln("Status redefinido com sucesso");

