<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

define("BUCKET", "uploads.wspot.com.br", true);
define("STORAGE_FOLDER", "guest-reports", true);

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Wideti\DomainBundle\Helpers\FileUpload;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$output     = new \Symfony\Component\Console\Output\ConsoleOutput();
$em         = $container->get('doctrine.orm.entity_manager');
$mongo      = $container->get('doctrine.odm.mongodb.document_manager');
$monolog    = $container->get('logger');
$fileUpload = new FileUpload($container->getParameter("aws_key"), $container->getParameter("aws_secret"), $container->getParameter("aws_bucket_name"), null);

if (!array_key_exists(1, $argv)) {
    $output->writeln("<comment>Informe o domínio do cliente.</comment>");
    exit;
}

if (!array_key_exists(2, $argv)) {
    $output->writeln("<comment>Informe o campanha para exportar o call to action.</comment>");
    exit;
}

if (!array_key_exists(3, $argv) || !array_key_exists(4, $argv)) {
    $output->writeln("<comment>Informe o range (data inicio, data fim) de data no formato: 2019-01-01.</comment>");
    exit;
}

//Parametros do script
$client = $argv[1];
$campaign = $argv[2];
$dateFrom = $argv[3];
$dateTo = $argv[4];

//Set das configurações do mongo
$mongoClient    = $mongo->getConnection()->getMongoClient();
$clientDatabase = $client;
$clientDatabase     = \Wideti\DomainBundle\Helpers\StringHelper::slugDomain($clientDatabase);
$database       = $mongoClient->$clientDatabase;
$collection     = $database->guests;
$fields         = $database->fields;
$field          = $fields->findOne(["isLogin"=>true]);

//Busca pelo nome da campanha
$baseCampaign = $em->getRepository("DomainBundle:Campaign")->findOneBy(["name" => $campaign]);

if (!$baseCampaign) {
    $output->writeln("<comment>Nenhuma campanha encontrada.</comment>");
    exit;
}

$campaignId = $baseCampaign->getId();

$callToActionCampaign = $em->getRepository("DomainBundle:CallToActionAccessData")->findBy(["campaign"=>$campaignId]);

if (!$callToActionCampaign) {
    $output->writeln("<comment>Nenhuma campanha call to action encontrada.</comment>");
    exit;
}

$exportFile = [];
$range      = str_replace('-', '', $dateFrom) . "_" . str_replace('-', '', $dateTo);
$fileName   = $client . "_callToAction_{$range}.csv";
$filePath   = getcwd() . "/{$fileName}";

$file = fopen($fileName, "w+");
fwrite($file,"Campanha;Tipo Call To Action;Visitante;Mac Address Visitante;Ponto de Acesso;URL;Data/Hora\n" );

foreach ($callToActionCampaign as $cta) {
    $guest = $collection->findOne(["mysql"=>$cta->getGuestId()]);

    $guest = ($guest)?$guest["properties"][$field['identifier']]:"N/A";

    $row = $cta->getCampaign()->getName().";";
    $row .= (($cta->getType() == 1)? 'Pré-login': 'Pós-login') .";";
    $row .= $guest.";";
    $row .= ($cta->getMacAddress()?:'Não informado') . ";";
    $row .= ($cta->getApMacAddress()?:'Não informado').";";
    $row .= ($cta->getUrl()?:'Não informado').";";
    $row .= date_format($cta->getViewDate(),'d/m/Y H:i:s');

   fwrite($file, $row."\n");
}

fclose($file);

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
