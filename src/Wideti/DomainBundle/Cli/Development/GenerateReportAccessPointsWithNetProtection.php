<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Wideti\DomainBundle\Helpers\FileUpload;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container   = $application->getKernel()->getContainer();


define("BUCKET", "uploads.wspot.com.br", true);
define("STORAGE_FOLDER", "internal-reports", true);

$mysqlHost = $container->getParameter('database_host');
$mysqlDatabase = $container->getParameter('database_name');
$mysqlUser = $container->getParameter('database_user');
$mysqlPassword = $container->getParameter('database_password');

$fileUpload = new FileUpload(
    $container->getParameter("aws_key"),
    $container->getParameter("aws_secret"),
    $container->getParameter("aws_bucket_name"),
    null);

$fileStream = fopen("fileTemp/licencas.csv", "r");
$fileCsv = fgetcsv($fileStream);

$listReport = [];
foreach ($fileCsv as $row){
    $resultList = getInfoApsIdentifier($row);
    $listReport = array_merge($listReport, $resultList);
}
$fileName = "reportApNetProtection.csv";
$filePath = "fileTemp/{$fileName}";

createCsvReport($listReport, $filePath);
sendCsvToStorage($fileName, $filePath, $fileUpload);
unlink($filePath);


function createCsvReport($reportList, $filePath){
    $fileStream = fopen($filePath, "a+");

    fputcsv($fileStream, ["identifier", "ap_status", "client_status", "domain"]);
    foreach ($reportList as $rowList){
        fputcsv($fileStream, [$rowList->identifier, $rowList->ap_status, $rowList->client_status, $rowList->domain]);
    }
}

function getInfoApsIdentifier($identifier){
    global $mysqlHost, $mysqlDatabase, $mysqlUser, $mysqlPassword;

    $conn = new PDO("mysql:host={$mysqlHost};dbname={$mysqlDatabase}", $mysqlUser, $mysqlPassword);

    $rs = $conn->prepare("select ap.identifier as identifier, 
    CASE ap.status WHEN 1 THEN 'Ativo' WHEN 0 THEN 'Inativo' ELSE ap.status END 'ap_status', 
    CASE c.status WHEN 1 THEN 'Ativo' WHEN 0 THEN 'Inativo' ELSE c.status END 'client_status', 
    c.domain as domain
    from access_points ap
    inner join clients c on ap.client_id = c.id
    where identifier=?");
    $rs->bindParam(1, $identifier);

    $listResult = [];
    if ($rs->execute()){
        if ($rs->rowCount() > 0){
            while ($row = $rs->fetch(PDO::FETCH_OBJ)){
                $listResult[] = $row;
            }
        }else{
            $listResult[] = (object) ['identifier' => $identifier, 'ap_status' => '--', 'client_status'=> '--', 'domain'=> '--'];
        }
    }
    return $listResult;
}

function sendCsvToStorage($fileName, $filePath, FileUpload $fileUpload) {
    try {
        $fileUpload->uploadFile(new UploadedFile($filePath, $fileName), $fileName, BUCKET, STORAGE_FOLDER);
        return "URL: " . $fileUpload->getUrl($fileName, BUCKET, STORAGE_FOLDER);
    } catch (\Exception $exception) {
        return "Nenhum arquivo foi enviado ao Storage.";
    }
}
