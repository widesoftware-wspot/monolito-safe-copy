<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Question\Question;
use Wideti\DomainBundle\Helpers\StringHelper;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Wideti\DomainBundle\Helpers\FileUpload;
use Wideti\DomainBundle\Service\AuditLogs\AuditEvent;
use Wideti\DomainBundle\Service\AuditLogs\EventTypes\Events;
use Wideti\DomainBundle\Service\AuditLogs\Kinds\Kinds;

$start = microtime(true);
$startMemory = memory_get_usage();

$kernel = new AppKernel('prod', true);
$kernel->boot();
$application = new Application($kernel);
$container = $application->getKernel()->getContainer();


$outputDirectory = __DIR__ . '/../../../../../reports_script'; // Caminho da pasta de saída

$input = new \Symfony\Component\Console\Input\ArgvInput([]);
$output = new \Symfony\Component\Console\Output\ConsoleOutput();
$question = new \Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper();

$em = $container->get('doctrine.orm.entity_manager');
$mongo = $container->get('doctrine.odm.mongodb.document_manager');
$elastic = $container->get('core.service.elastic_search');
$monolog    = $container->get('logger');
$auditor    = $container->get('core.service.auditor');


$output->writeln('<comment>==== Gerador de History Report ====</comment>');

$domain = $question->ask($input, $output, new Question('<info>Digite o domínio do cliente (string): </info>', null));
$dateFrom = $question->ask($input, $output, new Question('<info>Qual a data inicio do relatorio, no formato 2023-11-01 ?  </info>', null));
$dateTo = $question->ask($input, $output, new Question('<info>Qual a data fim do relatorio, no formato 2023-11-30 </info>', null));
$task = $question->ask($input, $output, new Question('<info>Informe a task de solicitação desta exportação. ex; 1140 </info>', null));


$client = $em->getRepository('DomainBundle:Client')->findOneBy([
    'domain' => $domain
]);

if (empty($client)) {
    $output->writeln("<comment>Cliente \"{$domain}\" não existe</comment>");
    exit;
}

$clientId     = $client->getId();
$customFileds = getCustomFields($mongo, $client->getDomain());
$customFiledsHeader = implode(';', array_values($customFileds));

$output->writeln("<comment>Gerando report, aguarde pode levar alguns minutos...</comment>");

$range      = str_replace('-', '', $dateFrom) . "_" . str_replace('-', '', $dateTo);
$fileName   = "relatorio_{$domain}_{$range}.csv";
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
    "size" => 15000,
    "query" => [
        "bool" => [
            "must" => [
                [
                    "term" => [
                        "client_id" => $clientId
                    ]
                ],
                [
                    "exists" => [
                        "field" => "acctstoptime"
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
$scrollTimeout = '10m';

$accounting = $elastic->searchScript('radacct', $search, \Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch::LAST_12_MONTHS);

try{
    $fileContent = "{$customFiledsHeader};Inicio;Fim;IP;Ponto de acesso;Ponto de acesso mac address;Download (bytes);Upload (bytes);\n";
    $mongoClient    = $mongo->getConnection()->getMongoClient();
    $clientDatabase = $client->getDomain();
    $clientDatabase = StringHelper::slugDomain($client->getDomain());
    $database       = $mongoClient->$clientDatabase;
    $collection     = $database->guests;
    $file = @fopen($fileName, "a+");
    $count          = 0;

    $batchSize = 10000;
    $currentBatch = [];
    
do {
    $guests_id = [];
    foreach ($accounting['hits']['hits'] as $access) {
        $guests_id[] = $access['_source']['username'];
    }

    if (!empty($guests_id)) {
        $guests = $collection->find(['mysql' => ['$in' => $guests_id]]);
        $guestMap = [];
        foreach ($guests as $guest) {
            $guestMap[$guest['mysql']] = $guest;
        }
        foreach ($accounting['hits']['hits'] as $access) {

            $guestId = $access['_source']['username'];
            $guest = isset($guestMap[$guestId]) ? $guestMap[$guestId] : null;
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

            if (isset($access['_source']['acctstarttime']) && isset($access['_source']['acctstoptime'])) {
                $currentBatch[] = implode(';', $customFiledsValues) .";".
                    $access['_source']['acctstarttime'] .";".
                    $access['_source']['acctstoptime'] .";".
                    $access['_source']['framedipaddress'] .";".
                    $access['_source']['calledstation_name'].";".
                    $access['_source']['calledstationid'].";".
                    $access['_source']['download'].";".
                    $access['_source']['upload'];

                $count++;
                if ($count % 10000 == 0) {
                    echo 'CONTADOR ' . $count . PHP_EOL;
                }
                if ((count($currentBatch) === $batchSize) || (count($currentBatch) < $batchSize)) {
                    writeBatchToFile($currentBatch, $fileContent, $file);
                    $currentBatch = [];
                }
            }
        }
    }

    $scrollId = $accounting['_scroll_id'];
    if (count($accounting['hits']['hits']) === 0) {
        $output->writeln("<comment>Finish");
        break;
    }


    $total = $accounting['hits']['total'];
    $output->writeln("<comment>TOTAL DE REGISTROS: {$total}</comment>");
    $accounting = $elastic->searchByScrollId($scrollId, $scrollTimeout);

} while (true);

    } catch (\Exception $exception) {
    $output->writeln("<error>Erro durante a geração do relatório: {$exception->getMessage()}</error>");
}




/**
 * @param $mongo
 * @param $clientDomain
 * @return array
 */
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
 * @param $currentBatch
 * @param $fileContent
 * @param $file
 * @return
 */
function writeBatchToFile($currentBatch, &$fileContent, $file)
{
    foreach ($currentBatch as $item) {
        $fileContent .= $item . "\n";
        fwrite($file, $item . "\n");
    }
}


$endMemory = memory_get_usage();
$memoryUsage = round(($endMemory - $startMemory) / (1024 * 1024), 2); // Convertendo bytes para megabytes
echo "Uso de memória: {$memoryUsage} MB\n";

$end = microtime(true);
$executionTime = round($end - $start, 2); // Tempo em segundos, arredondado para duas casas decimais
echo "Tempo de execução: {$executionTime} segundos\n";