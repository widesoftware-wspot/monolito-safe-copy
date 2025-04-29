<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$output     = new \Symfony\Component\Console\Output\ConsoleOutput();
$em         = $container->get('doctrine.orm.entity_manager');
$mongo      = $container->get('doctrine.odm.mongodb.document_manager');

$clients = $em->getRepository('DomainBundle:Client')->findAll();

$count = 0;

$arrayInvalid = [];

foreach ($clients as $client) {
    $mongoClient    = $mongo->getConnection()->getMongoClient();
    $clientDatabase = $client->getDomain();
    $database       = $mongoClient->$clientDatabase;
    $collection     = $database->guests;

    $guests = $collection->find([
        "emailIsValid" => false
    ]);

    foreach ($guests as $guest) {
        array_push($arrayInvalid, [
            'domain' => $client->getDomain(),
            'email' => $guest['email']
        ]);

        $count++;
    }
}

asort($arrayInvalid);

foreach ($arrayInvalid as $values) {
    $output->writeln("<comment>".$values['domain']." -> ".$values['email']."</comment>");
}

$output->writeln("<info>Total de [".$count."] e-mails inválidos.</info>");
