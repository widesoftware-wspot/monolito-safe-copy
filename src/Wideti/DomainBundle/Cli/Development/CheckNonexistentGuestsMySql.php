<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$output     = new \Symfony\Component\Console\Output\ConsoleOutput();

$mongo      = $container->get('doctrine.odm.mongodb.document_manager');
$em         = $container->get('doctrine')->getEntityManager('default');
$con        = $em->getConnection();

$emptyFile  = true;

$exportFile = [];
$file       = "checkNonexistentGuests_MySql.csv";
$outfile    = "$file";

$fp = fopen($outfile, "wb");
array_push($exportFile, "id_mysql");

$output = new \Symfony\Component\Console\Output\ConsoleOutput();

$mongoClient    = $mongo->getConnection()->getMongoClient();
$clientDatabase = 'kopclub';
$database       = $mongoClient->$clientDatabase;
$collection     = $database->guests;

$search = [
    'created' => [
        '$gte' => new \MongoDate(strtotime("2019-12-03 14:00:00")),
        '$lte' => new \MongoDate(strtotime("2019-12-03 14:30:00"))
    ]
];

$guests = $collection->find($search);

$output->writeln("<info>" . $guests->count() . " visitantes encontrados</info>");

$progressBar = new \Symfony\Component\Console\Helper\ProgressBar($output, $guests->count());
$progressBar->setBarCharacter('<fg=magenta>=</>');
$progressBar->setProgressCharacter("|");

foreach ($guests as $guest) {
    $progressBar->advance();

    if (!array_key_exists('mysql', $guest)) {
        echo $guest;
        die;
    }

    $find = $em->getRepository('DomainBundle:Guests')
        ->findOneBy([
            'id' => $guest['mysql']
        ]);

    if (!$find) {
        array_push(
            $exportFile,
            $guest['mysql']
        );
    }
}

foreach ($exportFile as $item) {
    @fwrite($fp, $item . "\n");
}

@fclose($fp);
