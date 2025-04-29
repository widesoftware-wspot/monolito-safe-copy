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
    $collection     = $database->guests;
    $search         = [];

    $guests = $collection->find();

    $output->writeln("<info>".$guests->count()." visitantes encontrados</info>");

    $progressBar = new \Symfony\Component\Console\Helper\ProgressBar($output, $guests->count());
    $progressBar->setBarCharacter('<fg=magenta>=</>');
    $progressBar->setProgressCharacter("|");

    foreach ($guests as $guest) {

        if (array_key_exists('social', $guest)) {
            $uniqueArray    = array_map("unserialize", array_unique(array_map("serialize", $guest['social'])));
            $newSocial      = [];

            $i = 0;

            foreach ($uniqueArray as $social) {
                if ($i > 0 && in_array($social, $newSocial)) {
                    continue;
                }

                array_push($newSocial, $social);

                $i++;
            }

            $collection->update(
                [
                    "email" => $guest["email"]
                ],
                [
                    '$set' => [
                        "social" => $newSocial
                    ]
                ]
            );
        }

        if (array_key_exists('accessData', $guest)) {
            $newAccessData = [];

            foreach ($guest['accessData'] as $accessData) {
                if ($accessData['macaddress'] == "") {
                    continue;
                }
                array_push($newAccessData, $accessData);
            }

            $collection->update(
                [
                    "email" => $guest["email"]
                ],
                [
                    '$set' => [
                        "accessData" => $newAccessData
                    ]
                ]
            );

        }

        $progressBar->advance();
    }
    $progressBar->finish();
    $output->writeln("");
    $output->writeln("<comment>-- fim do cliente --</comment>");
}
