<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Question\Question;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$output     = new \Symfony\Component\Console\Output\ConsoleOutput();
$em         = $container->get('doctrine.orm.entity_manager');
$mongo      = $container->get('doctrine.odm.mongodb.document_manager');
$elastic    = $container->get('core.service.elastic_search');

$query = [
    'size' => 9999,
    'query' => [
        'term' => [
            'client_id' => 13279
        ]
    ]
];

$result = $elastic->search(
    'radacct',
    $query,
    'wspot_2019_08'
);

$fp = fopen('acct_2019_08.txt', 'w');

foreach ($result['hits']['hits'] as $acct) {
    fwrite($fp, json_encode($acct) . "\n");
}
//$client = $em->getRepository("DomainBundle:Client")
//    ->findOneBy([
//        'domain' => 'bahrembar'
//    ]);
//
//$domain         = $client->getDomain();
//$mongoClient    = $mongo->getConnection()->getMongoClient();
//$database       = $mongoClient->{$domain};
//$collection     = $database->guests;
//
//$guests = $collection->find();
//
//$fp = fopen('visitantes.txt', 'w');
//
//foreach ($guests as $guest) {
//    fwrite($fp, json_encode($guest) . "\n");
//}
//
fclose($fp);
