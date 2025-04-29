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
    ->getActiveClients()
;

$field = $argv[1];
$count = 0;
$totalGuests = 0;
foreach ($clients as $client) {
    $mongoClient    = $mongo->getConnection()->getMongoClient();
    $clientDatabase = StringHelper::slugDomain($client->getDomain());
    $database       = $mongoClient->$clientDatabase;
    $collection     = $database->fields;

    $search = $collection->find([
        'identifier' => $field
    ]);


    if ($search->count() > 0) {
        $guests = $em->getRepository('DomainBundle:Guests')->countByClient($client);
        $totalGuests += $guests;
        $count++;
    }
}

echo $count;
echo $totalGuests;
