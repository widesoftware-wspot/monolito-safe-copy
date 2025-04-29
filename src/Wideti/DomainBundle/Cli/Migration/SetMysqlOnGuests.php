<?php
require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application    = new Application($kernel);
$container      = $application->getKernel()->getContainer();
$output         = new \Symfony\Component\Console\Output\ConsoleOutput();
$em             = $container->get('doctrine.orm.entity_manager');
$connection     = $em->getConnection();
$mongo          = $container->get('doctrine.odm.mongodb.document_manager');
$mongoClient    = $mongo->getConnection()->getMongoClient();

if (!isset($argv[1])) {
    $output->writeln("<comment>MySQL ID parameter must be an integer value</comment>");
    exit;
}

$client = $em->getRepository('DomainBundle:Client')->find([ 'id' => $argv[1] ]);

if (!$client) {
    $output->writeln("<comment>Domain not found for MySQL ID #{$argv[1]}</comment>");
    exit;
}
$db = \Wideti\DomainBundle\Helpers\StringHelper::slugDomain($client->getDomain());
$guests = $mongoClient->{$db}->guests->find();

foreach ($guests as $guest) {
    $statement = $connection->prepare("UPDATE visitantes SET client_id = {$argv[1]} WHERE id = {$guest['mysql']};");
    $statement->execute();
}
