<?php
require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Wideti\DomainBundle\Helpers\StringHelper;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container   = $application->getKernel()->getContainer();
$output      = new \Symfony\Component\Console\Output\ConsoleOutput();

unset($argv[0]);

if (count($argv) == 0) {
    $output->writeln("Ao menos um módulo deve ser informado!");
    exit;
}

$domainCount = 0;
$domains     = $container->get('doctrine')->getEntityManager('default')->getRepository('DomainBundle:Client')->findAll();

asort($domains);
unset($argv[0]);

foreach ($argv as $module) {
    $output->writeln("Módulo: {$module}");
    $module = strtolower($module);

    foreach ($domains as $domain) {
        $mongoClient     = $container->get('doctrine.odm.mongodb.document_manager')->getConnection()->getMongoClient();
        $db = StringHelper::slugDomain($domain->getDomain());
        $collectionsList = $mongoClient->{$db}->listCollections();

        foreach ($collectionsList as $collection) {
            if (strtolower($collection->getName()) === $module) {
                $domainCount++;
                $output->writeln("- {$domain->getDomain()};");
                break;
            }
        }
    }

    $output->writeln("Total de domínios que possuem o módulo {$module}: {$domainCount}");
    $output->writeln("=================================================================");
    reset($domains);
}
