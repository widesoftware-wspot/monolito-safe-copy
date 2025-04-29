<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$input      = new \Symfony\Component\Console\Input\ArgvInput([]);
$output     = new \Symfony\Component\Console\Output\ConsoleOutput();
$questions  = new \Symfony\Component\Console\Helper\QuestionHelper();

$em         = $container->get('doctrine.orm.entity_manager');
$mongo      = $container->get('doctrine.odm.mongodb.document_manager');
$monolog    = $container->get('logger');

$clients    = $em->getRepository('DomainBundle:Client')
    ->findAll()
;

if (count($clients) == 0) {
    $output->writeln("<comment>Nenhum cliente encontrado.</comment>");
}

foreach ($clients as $client) {

    $output->writeln("");
    $output->writeln("<info>Cliente {$client->getDomain()} selecionado</info>");

    $mongoClient    = $mongo->getConnection()->getMongoClient();
    $clientDatabase = \Wideti\DomainBundle\Helpers\StringHelper::slugDomain($client->getDomain());
    $database       = $mongoClient->$clientDatabase;
    $collection     = $database->groups;
    $search         = [];

    $groups = $collection->find();

    $output->writeln("<info>".$groups->count()." grupos encontrados</info>");

    $progressBar = new \Symfony\Component\Console\Helper\ProgressBar($output, $groups->count());
    $progressBar->setBarCharacter('<fg=magenta>=</>');
    $progressBar->setProgressCharacter("|");

    $newChoices = ["65536" => "64 Kbps", "131072" => "128 Kbps", "262144" => "256 Kbps", "524288" => "512 Kbps", "1048576" => "1 Mbps", "2097152" => "2 Mbps", "3145728" => "3 Mbps", "5242880" => "5 Mbps", "10485760" => "10 Mbps", "20971520" => "20 Mbps", "31457280" => "30 Mbps", "41943040" => "40 Mbps", "52428800" => "50 Mbps"];

    foreach ($groups as $group) {
    $keys = array_keys($group['configurations'][2]['configurationValues']);

    foreach ($keys as $key){
        $type = $group['configurations'][2]['configurationValues'][$key]['type'];
        $keyName = $group['configurations'][2]['configurationValues'][$key]['key'];

        if( $type === 'choice' && ($keyName === 'bandwidth_download_limit' || $keyName === 'bandwidth_upload_limit') ){
            $collection->update(
                [
                    "name" => $group["name"]
                ],
                [
                    '$set' => [
                        "configurations.2.configurationValues.$key.params.choices" => $newChoices
                    ]
                ],
                [
                    '$multi' => true
                ]
            );

            $collection->update(
                [
                    "name" => $group["name"]
                ],
                [
                    '$set' => [
                        "configurations.2.configurationValues.$key.params.choices" => $newChoices
                    ]
                ],
                [
                    '$multi' => true
                ]
            );
        }
    }
        $progressBar->advance();
    }
    $progressBar->finish();
    $output->writeln("");
    $output->writeln("<comment>-- fim do cliente --</comment>");
}
