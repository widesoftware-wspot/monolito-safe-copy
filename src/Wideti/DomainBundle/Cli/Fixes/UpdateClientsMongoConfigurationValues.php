<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Wideti\DomainBundle\Document\Group\AccessPoint;
use Wideti\DomainBundle\Document\Group\AccessPointGroup;
use Wideti\DomainBundle\Document\Group\Configuration;
use Wideti\DomainBundle\Document\Group\Group;
use Wideti\DomainBundle\Helpers\StringHelper;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$output     = new \Symfony\Component\Console\Output\ConsoleOutput();

$application = new Application($kernel);
$container = $application->getKernel()->getContainer();

$em = $container->get('doctrine.orm.entity_manager');
$mongo = $container->get('doctrine.odm.mongodb.document_manager');

$clients = $em
    ->getRepository('DomainBundle:Client')
    ->findAll();

/**
 * MONGODB
 */

foreach ($clients as $key => $client) {
    $domain = $client->getDomain();
    $mongoClient = $mongo->getConnection()->getMongoClient();
    $repository
        = $mongo->getRepository("Wideti\DomainBundle\Document\Group\Group");
    $clientDatabase = StringHelper::slugDomain($domain);
    $database = $mongoClient->$clientDatabase;
    $groups = $database->groups;
    if ($groups->find()->next() == null) {
        continue;
    }
    $output->writeln("--------- PROCESSANDO CLIENT $key: $domain  ---------");
    foreach ($groups->find() as $group) {
        $i = 0;
        $output->writeln("--------- grupo_id: ". $group['_id']);
        foreach ($group["configurations"] as $configuration) {
            $group["configurations"][$i]['configurationValues']
                = array_values($group["configurations"][$i]['configurationValues']);
            $i++;
        }
        $groups->update(["_id" => $group['_id']], [
            '$set' => [
                "configurations" => $group["configurations"]
            ]
        ]);

    }
    $output->writeln("\n");
}
$output->writeln("----------- FIM -----------");
