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
$container   = $application->getKernel()->getContainer();
$output      = new \Symfony\Component\Console\Output\ConsoleOutput();
$mongo       = $container->get('doctrine.odm.mongodb.document_manager');
$em          = $container->get('doctrine')->getEntityManager('default');
$clients     = $em->getRepository('DomainBundle:Client')->findAll();

$toCheck = [
    'block_per_time_time',
    'block_per_time_period'
];

$time   = [];
$period = [];

foreach ($clients as $client) {
    $mongoClient    = $mongo->getConnection()->getMongoClient();
    $clientDatabase = $client->getDomain();
    $clientDatabase = StringHelper::slugDomain($clientDatabase);
    $database       = $mongoClient->$clientDatabase;
    $groups         = $database->groups->find();

    foreach ($groups as $group) {
        foreach ($group['configurations'] as $config) {
            if ($config['shortcode'] != 'block_per_time') continue;

            foreach ($config['configurationValues'] as $value) {
                if (!in_array($value['key'], $toCheck)) continue;

                if ($value['key'] == 'block_per_time_time' && $value['value'] != '') {
                    array_push($time, $value['value']);
                }

                if ($value['key'] == 'block_per_time_period' && $value['value'] != '') {
                    array_push($period, $value['value']);
                }
            }
        }
    }
}

echo "TIME\n" . implode(", ", array_unique($time));
echo "\nPERIOD\n" . implode(", ", array_unique($period));
