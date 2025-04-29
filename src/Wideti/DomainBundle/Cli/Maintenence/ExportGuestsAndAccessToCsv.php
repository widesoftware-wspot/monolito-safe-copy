<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

define("BUCKET", "reports.wspot.com.br", true);
define("SKIP_NUMBER", 1000, true);

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Helpers\StringHelper;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$output     = new \Symfony\Component\Console\Output\ConsoleOutput();

$elastic    = $container->get('core.service.elastic_search');
$mongo      = $container->get('doctrine.odm.mongodb.document_manager');
$monolog    = $container->get('logger');
$auditor    = $container->get('core.service.auditor');

$fileUpload = new FileUpload($container->getParameter("aws_key"), $container->getParameter("aws_secret"), $container->getParameter("aws_bucket_name"), null);


$em         = $container->get('doctrine')->getEntityManager('default');
$con        = $em->getConnection();

if (!array_key_exists(1, $argv)) {
    $output->writeln("<comment>Informe o domínio do cliente.</comment>");
    exit;
}

if (!array_key_exists(3, $argv) || !array_key_exists(4, $argv)) {
    $output->writeln("<comment>Informe o range (data inicio, data fim) de data no formato: 2019-01-01.</comment>");
    exit;
}

if (!array_key_exists(5, $argv)) {
    $output->writeln("<comment>Informe a task de solicitação desta exportação.</comment>");
    exit;
}


$domain     = $argv[1];
$reportType = $argv[2];
$dateFrom   = $argv[3];
$dateTo     = $argv[4];
$task       = $argv[5];

$options = ['acessos', 'visitantes'];

if (!in_array($reportType, $options)) {
    $output->writeln("<comment>Opção de relatório inválida, escolha 'acessos' ou 'visitantes'.</comment>");
    exit;
}

$emptyFile  = true;

$client     = $em->getRepository('DomainBundle:Client')
    ->findOneBy([
        'domain' => $domain
    ])
;

if (!$client) {
    $output->writeln("<comment>Nenhum cliente encontrado.</comment>");
    exit;
}

$clientId     = $client->getId();
$customFileds = getCustomFields($mongo, $client->getDomain());
$customFiledsHeader = implode(';', array_values($customFileds));

if ($reportType == 'acessos') {
    $exportFile = [];
    $range      = str_replace('-', '', $dateFrom) . "_" . str_replace('-', '', $dateTo);
    $fileName   = "geracao_completa_manual_{$range}.csv";
    $filePath   = @getcwd()."/{$fileName}";

    $event = $auditor
        ->newEvent()
        ->withClient($client->getId())
        ->withSource(Kinds::system(), $clientId)
        ->onTarget(Kinds::guestsReport(), $clientId)
        ->withType(Events::export())
        ->addDescription(AuditEvent::PT_BR, "Sistema exportou os acessos do cliente {$clientId} para cumprir a  task {$task}")
        ->addDescription(AuditEvent::EN_US, "System export all access_registers from client {$clientId} to accomplish the task {$task}")
        ->addDescription(AuditEvent::ES_ES, "Acceso de cliente {$clientId} exportado por el sistema para cumprir a task {$task}");
    $auditor->push($event);

    $search = [
        "size"  => 199999,
        "query" => [
            "bool" => [
                "must" => [
                    [
                        "term" => [
                            "client_id" => $clientId
                        ]
                    ],
                    [
                        "range" => [
                            "acctstarttime" => [
                                "gte" => "{$dateFrom} 00:00:00",
                                "lte" => "{$dateTo} 23:59:59"
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    $accounting = $elastic->search('radacct', $search, \Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch::LAST_12_MONTHS);

    if ($accounting['hits']['total'] > 0) {
        $file = @fopen($fileName, "a+");
        @fwrite($file, "{$customFiledsHeader};start_time;stop_time;guest_ip;guest_mac_address;ap_mac_address\n");
        @fclose($file);

        $mongoClient    = $mongo->getConnection()->getMongoClient();
        $clientDatabase = $client->getDomain();
        $clientDatabase = StringHelper::slugDomain($client->getDomain());
        $database       = $mongoClient->$clientDatabase;
        $collection     = $database->guests;

        $file = @fopen($fileName, "a+");

        foreach ($accounting['hits']['hits'] as $access) {
            $guest = $collection->findOne([
                "mysql" => $access['_source']['username']
            ]);

            $customFiledsValues = [];

            foreach ($customFileds as $key=>$v) {
                $value = '';
                if (isset($guest['properties'][$key])) {
                    $value = $guest['properties'][$key];
                    if ($value instanceof MongoDate) {
                        $value = date('d/m/Y', $value->sec);
                    }
                }

                array_push($customFiledsValues, $value);
            }

            if (!isset($access['_source']['acctstoptime'])){
                $access['_source']['acctstoptime'] = '';
            }

            array_push(
                $exportFile,
                implode(';', $customFiledsValues) .";".
                $access['_source']['acctstarttime'] .";".
                $access['_source']['acctstoptime'] .";".
                $access['_source']['framedipaddress'] .";".
                $access['_source']['callingstationid'].";".
                $access['_source']['calledstationid']
            );
        }

        foreach ($exportFile as $item) {
            @fwrite($file, $item . "\n");
        }

        @fclose($file);

        $folder = "{$clientId}/{$reportType}";

        $transferLog = sendToStorage($fileName, $filePath, $fileUpload,$client, 'access_historic', $folder);
        $output->writeln("<comment>{$transferLog}</comment>");
        @unlink($filePath);

        $total = $accounting['hits']['total'];
        $output->writeln("<comment>TOTAL DE REGISTROS: {$total}</comment>");
    }
}

if ($reportType == 'visitantes') {
    $exportFile = [];
    $range      = str_replace('-', '', $dateFrom) . "_" . str_replace('-', '', $dateTo);
    $fileName   = "geracao_completa_manual_{$range}.csv";
    $filePath   = @getcwd()."/{$fileName}";
    $file       = @fopen($fileName, "a+");

    $event = $auditor
        ->newEvent()
        ->withClient($client->getId())
        ->withSource(Kinds::system(), $clientId)
        ->onTarget(Kinds::guestsReport(), $clientId)
        ->withType(Events::export())
        ->addDescription(AuditEvent::PT_BR, "Sistema exportou os visitantes do cliente {$clientId} para cumprir a  task {$task}")
        ->addDescription(AuditEvent::EN_US, "System export all guests from client {$clientId}  to accomplish the task {$task}")
        ->addDescription(AuditEvent::ES_ES, "Visitantes exportados por el sistema del cliente {$clientId} para cumprir a task {$task}");
    $auditor->push($event);

    array_push($exportFile, "{$customFiledsHeader};Ponto de acesso de cadastro;Locale;Data Cadastro;Última visita;Status");

    $mongoClient    = $mongo->getConnection()->getMongoClient();
    $clientDatabase = StringHelper::slugDomain($client->getDomain());
    $database       = $mongoClient->$clientDatabase;
    $collection     = $database->guests;
    $search         = [];

    $search = [
        'created' => [
            '$gte' => new MongoDate(strtotime("{$dateFrom} 00:00:00")),
            '$lte' => new MongoDate(strtotime("{$dateTo} 23:59:59"))
        ]
    ];

    $guests = $collection->find($search);

    if ($guests->count() > 0) {
        $emptyFile = false;

        foreach ($guests as $guest) {
            $customFiledsValues = [];

            foreach ($customFileds as $key=>$v) {
                $value = 'N/I';
                if (isset($guest['properties'][$key])) {
                    $value = $guest['properties'][$key];
                    if ($value instanceof MongoDate) {
                        $value = date('d/m/Y', $value->sec);
                    }
                }

                if ($key === 'guest_type' && $client->getDomain() === 'rioquente') {
                    $value = (isset($guest['properties'][$key]) && $guest['properties'][$key] == 1) ? "Passando o dia no Hot Park" : "Hospedado no Rio Quente Resorts";
                }

                array_push($customFiledsValues, mb_convert_encoding($value, 'Windows-1252', 'UTF-8'));
            }

            $authorizeEmail = 'N/I';
            $registrationMacAddress = 'N/I';

            if (array_key_exists('authorizeEmail', $guest)) {
                $authorizeEmail = $guest['authorizeEmail'] ?: 'N/I';
            }

            if (array_key_exists('registrationMacAddress', $guest)) {
                $registrationMacAddress = $guest['registrationMacAddress'] ?: 'N/I';
            }

            $created    = new \DateTime(date('Y-m-d H:i:s', $guest['created']->sec));

            $lastAccess = 'N/I';
            if (isset($guest['lastAccess'])) {
                $lastAccess = new \DateTime(date('Y-m-d H:i:s', $guest['lastAccess']->sec));
                $lastAccess = date_format($lastAccess, 'Y-m-d H:i:s');
            }

            $guestFoo = new \Wideti\DomainBundle\Document\Guest\Guest();
            $guestFoo->setStatus($guest['status']);
            $status = $guestFoo->getStatusAsString();

            array_push(
                $exportFile,
                implode(';', $customFiledsValues) .";".
                $registrationMacAddress .";".
                $guest['locale'] .";".
                date_format($created, 'Y-m-d H:i:s') .";".
                $lastAccess .";".
                $status
            );
        }

        foreach ($exportFile as $item) {
            @fwrite($file, $item . "\n");
        }

        @fclose($file);

        $folder = "{$clientId}/{$reportType}";

        $transferLog = sendToStorage($fileName, $filePath, $fileUpload,$client, 'guest', $folder);
        $output->writeln("<comment>{$transferLog}</comment>");
        @unlink($filePath);
    }
}

function getCustomFields($mongo, $clientDomain)
{
    $mongoClient    = $mongo->getConnection()->getMongoClient();
    $clientDatabase = StringHelper::slugDomain($clientDomain);
    $database       = $mongoClient->$clientDatabase;

    $fields = [];
    $search = $database->fields->find();

    foreach ($search as $item) {
        $fields[$item['identifier']] = $item['name']['pt_br'];
    }

    return $fields;
}

/**
 * @param $fileName
 * @param $filePath
 * @param FileUpload $fileUpload
 * @return string
 */
function sendToStorage($fileName, $filePath, FileUpload $fileUpload,$client, $reportType, $folder) {
    try {
        $fileUpload->uploadCLIReports(BUCKET, $client, $reportType, $filePath, $fileName);
        return "URL: " . $fileUpload->getUrl($fileName, BUCKET, $folder);
    } catch (\Exception $exception) {
        echo $exception->getMessage();
        return "Nenhum arquivo foi enviado ao Storage.";
    }
}
