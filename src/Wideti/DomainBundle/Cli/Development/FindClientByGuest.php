<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Wideti\DomainBundle\Helpers\StringHelper;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$output     = new \Symfony\Component\Console\Output\ConsoleOutput();
$em         = $container->get('doctrine.orm.entity_manager');
$mongo      = $container->get('doctrine.odm.mongodb.document_manager');
$monolog    = $container->get('logger');

$clients = $em->getRepository('DomainBundle:Client')
    ->findAll()
;

$email = $argv[1];

foreach ($clients as $client) {
    $mongoClient    = $mongo->getConnection()->getMongoClient();
    $clientDatabase = StringHelper::slugDomain($client->getDomain());
    $database       = $mongoClient->$clientDatabase;
    $collection     = $database->guests;

    $guests = $collection->find([
        'properties.email' => $email
    ]);

    if ($guests->count() > 0) {
        echo "Cliente: " . $client->getDomain() . "\n";
    }
}
