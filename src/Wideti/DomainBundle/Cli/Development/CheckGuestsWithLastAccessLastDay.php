<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Wideti\DomainBundle\Helpers\StringHelper;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$output     = new \Symfony\Component\Console\Output\ConsoleOutput();

$mongo      = $container->get('doctrine.odm.mongodb.document_manager');
$em         = $container->get('doctrine')->getEntityManager('default');
$con        = $em->getConnection();

$emptyFile  = true;

$clients    = $em->getRepository('DomainBundle:Client')
    ->findAll()
;

$total = 0;

foreach ($clients as $client) {
    $mongoClient = $mongo->getConnection()->getMongoClient();
    $clientDatabase = StringHelper::slugDomain($client->getDomain());
    $database = $mongoClient->$clientDatabase;
    $collection = $database->guests;

    $search = $collection->find([
        'created' => [
            '$gte' => new MongoDate(strtotime('2019-08-13 00:00:00')),
            '$lte' => new MongoDate(strtotime('2019-08-20 23:59:59'))
        ]
    ]);

    $count = $search->count();
    $output->writeln("<info>" . $client->getDomain() . " - {$count}</info>");

    $total += $count;
}

echo $total;
echo $total/7;
