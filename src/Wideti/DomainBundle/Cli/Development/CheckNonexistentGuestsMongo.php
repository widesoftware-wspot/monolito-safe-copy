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
$file       = "checkNonexistentGuests_Mongo.csv";
$outfile    = "$file";

$fp = fopen($outfile, "wb");
array_push($exportFile, "guest_id_mysql");

$output = new \Symfony\Component\Console\Output\ConsoleOutput();

$queryVendor = $con->prepare("
    SELECT id FROM visitantes WHERE client_id = 13831 AND id > 27648024 AND id < 27669255 LIMIT 300000
");
$queryVendor->setFetchMode(PDO::FETCH_CLASS, \Wideti\DomainBundle\Entity\Guests::class);
$queryVendor->execute();

$guests = $queryVendor->fetchAll();

$progressBar = new \Symfony\Component\Console\Helper\ProgressBar($output, count($guests));
$progressBar->setBarCharacter('<fg=magenta>=</>');
$progressBar->setProgressCharacter("|");

$mongoClient    = $mongo->getConnection()->getMongoClient();
$clientDatabase = 'kopclub';
$database       = $mongoClient->$clientDatabase;
$collection     = $database->guests;

$guestId = null;

foreach ($guests as $guest) {
    $progressBar->advance();

    $find = $collection->findOne([
        "mysql" => (int)$guest->getId()
    ]);

    if (!$find) {
        array_push(
            $exportFile,
            $guest->getId()
        );
    }

    $guestId = $guest->getId();
}

$progressBar->finish();
$output->writeln("");
$output->writeln("<comment>-- fim --</comment>");
$output->writeln("<comment>------------------- " . $guestId . " -------------------</comment>");

foreach ($exportFile as $item) {
    @fwrite($fp, $item . "\n");
}

@fclose($fp);
