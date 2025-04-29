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
$clients = $em->getRepository('DomainBundle:Client')->findAll();

foreach ($clients as $client) {
    $mongoClient    = $mongo->getConnection()->getMongoClient();
    $clientFromDatabase = \Wideti\DomainBundle\Helpers\StringHelper::slugDomain($client->getDomain());
    $database       = $mongoClient->$clientFromDatabase;
    $collection = $database->selectCollection('groups');
    $cursor = $collection->find();

    foreach ($cursor as $document) {
        if ($document["name"] === "Testes") {
            $mongoClient->$clientFromDatabase->groups->remove($document);
        }
    }

    $mongo->close();
}




