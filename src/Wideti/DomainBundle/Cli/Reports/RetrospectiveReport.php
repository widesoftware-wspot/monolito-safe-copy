<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$em         = $container->get('doctrine.orm.entity_manager');
$mongo      = $container->get('doctrine.odm.mongodb.document_manager');
$monolog    = $container->get('logger');
$reportMail = $application->getKernel()->getContainer()->get('core.service.retrospective_report');

$clients = $em->getRepository('DomainBundle:Client')
    ->findByStatus(\Wideti\DomainBundle\Entity\Client::STATUS_ACTIVE)
;

foreach ($clients as $client) {
    $database = \Wideti\DomainBundle\Helpers\StringHelper::slugDomain($client->getDomain());
    $mongo
        ->getConfiguration()
        ->setDefaultDB($database)
    ;

    $newMongo = $mongo->create(
        $mongo->getConnection(),
        $mongo->getConfiguration(),
        $mongo->getEventManager()
    );

    $reportMail->setMongo($newMongo);
    $reportMail->init($client);
    sleep(5);
}
