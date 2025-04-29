<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Wideti\DomainBundle\Helpers\StringHelper;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$output = new \Symfony\Component\Console\Output\ConsoleOutput();

$application = new Application($kernel);
$container = $application->getKernel()->getContainer();

$em = $container->get('doctrine.orm.entity_manager');
$mongo = $container->get('doctrine.odm.mongodb.document_manager');

$clientList = $em->getRepository('DomainBundle:Client')
    ->createQueryBuilder('c')
    ->getQuery()
    ->getArrayResult();

$clientesGuestGroupsNull = [];
foreach ($clientList as $clients) {
    $mongoClient = $mongo->getConnection()->getMongoClient();
    $clientDatabase = StringHelper::slugDomain($clients['domain']);
    $database = $mongoClient->$clientDatabase;
    $guests = $database->guests->find();
    foreach ($guests as $guest) {
        if (!isset($guest['group'])) {
            array_push($clientesGuestGroupsNull, $clients['domain']);
            break;
        }
    }
    $output->writeln("\n");
}

echo $clientesGuestGroupsNull;
