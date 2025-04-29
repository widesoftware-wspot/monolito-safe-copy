<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

define("BUCKET", "uploads.wspot.com.br", true);
define("STORAGE_FOLDER", "guest-reports", true);
define("SKIP_NUMBER", 1000, true);

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Helpers\StringHelper;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$output     = new \Symfony\Component\Console\Output\ConsoleOutput();

$elastic    = $container->get('core.service.elastic_search');
$mongo      = $container->get('doctrine.odm.mongodb.document_manager');
$monolog    = $container->get('logger');
$fileUpload = new FileUpload($container->getParameter("aws_key"), $container->getParameter("aws_secret"), $container->getParameter("aws_bucket_name"), null);

$em         = $container->get('doctrine')->getEntityManager('default');
$con        = $em->getConnection();
$emptyFile  = true;

$clients    = $em->getRepository('DomainBundle:Client')
    ->findAll()
;

$exportFile = [];
$fileName   = "visitantes_cadastrados_" . str_replace('-', '', date('Ymd')) . ".csv";
$filePath   = @getcwd() . "/{$fileName}";
$file       = @fopen($fileName, "a+");

array_push(
    $exportFile,
    "ID Cliente; Domínio; Status; Jan 19; Fev 19; Mar 19; Abr 19; Mai 19; Jun 19; Jul 19; Ago 19; Set 19; Out 19; Nov 19; Dez 19; Jan 20; Fev 20; Mar 20; Abr 20"
);

$mongoClient = $mongo->getConnection()->getMongoClient();

foreach ($clients as $client) {
    $clientDatabase = $client->getDomain();
    $clientDatabase = StringHelper::slugDomain($clientDatabase);
    $database = $mongoClient->$clientDatabase;
    $collection = $database->guests;

    $start = new DateTime('2019-01-01');
    $end = new DateTime('2020-04-05');
    $interval = DateInterval::createFromDateString('1 month');
    $period = new DatePeriod($start, $interval, $end);

    $totalCadastros = [];
    foreach ($period as $dt) {
        $guests = $collection->find([
            'created' => [
                '$gte' => new MongoDate(strtotime($dt->format("Y-m-d 00:00:00"))),
                '$lte' => new MongoDate(strtotime($dt->modify("next month")->format("Y-m-d 00:00:00")))
            ]
        ]);

        array_push($totalCadastros, $guests->count());
    }

    array_push(
        $exportFile,
        $client->getId() . ";" .
        $client->getDomain() . ";" .
        $client->getStatusAsString() . ";" .
        implode(";", $totalCadastros)
    );

    echo $client->getDomain() . "\n";
}

foreach ($exportFile as $item) {
    @fwrite($file, $item . "\n");
}

@fclose($file);

$transferLog = sendToStorage($fileName, $filePath, $fileUpload);
$output->writeln("<comment>{$transferLog}</comment>");
@unlink($filePath);

/**
 * @param $fileName
 * @param $filePath
 * @param FileUpload $fileUpload
 * @return string
 */
function sendToStorage($fileName, $filePath, FileUpload $fileUpload) {
    try {
        $fileUpload->uploadFile(new UploadedFile($filePath, $fileName), $fileName, BUCKET, STORAGE_FOLDER);
        return "URL: " . $fileUpload->getUrl($fileName, BUCKET, STORAGE_FOLDER);
    } catch (\Exception $exception) {
        return "Nenhum arquivo foi enviado ao Storage.";
    }
}
