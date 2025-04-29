<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once __DIR__ . "/../../../../../app/bootstrap.php.cache";
require_once __DIR__ . "/../../../../../app/AppKernel.php";

use Symfony\Bundle\FrameworkBundle\Console\Application;

$kernel = new AppKernel("prod", true);
$kernel->boot();

$application = new Application($kernel);
$container   = $application->getKernel()->getContainer();
$output      = new \Symfony\Component\Console\Output\ConsoleOutput();
$elastic     = $container->get("core.service.elastic_search");
$validation  = "";

if (!isset($argv[1])) {
    $validation = "- ID do cliente (MySQL) deve ser informado;\n";
}

if (!isset($argv[2])) {
    $validation .= "- Início do Período deve ser informado [yyyy-mm-dd];\n";
}

if (!isset($argv[3])) {
    $validation .= "- Fim do Período deve ser informado [yyyy-mm-dd];\n";
}

if (!isset($argv[4])) {
    $validation .= "- Router Mode deve ser informado [router/bridge];\n";
}

if (!empty(trim($validation))) {
    $output->writeln("<comment>{$validation}</comment>");
    exit;
}

$clientId   = $argv[1];
$dateFrom   = $argv[2];
$dateTo     = $argv[3];
$routerMode = $argv[4];

if ($routerMode == "router") {
    $downloadField = "acctoutputoctets";
    $uploadField   = "acctinputoctets";
} elseif ($routerMode == "bridge") {
    $downloadField = "acctinputoctets";
    $uploadField   = "acctoutputoctets";
} else {
    $output->writeln("<comment>Router Mode inválido. Deve ser: [router/bridge]</comment>");
    exit;
}

$search = [
    "query" => [
        "filtered" => [
            "query" => [
                "term" => [ "clientId" => $clientId ],
            ],
            "filter" => [
                "bool" => [
                    "must" => [
                        [
                            "range" => [
                                "date" => [
                                    "gte" => $dateFrom,
                                    "lte" => $dateTo,
                                    "format" => "yyyy-MM-dd"
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ],
    "size" => 1,
    "aggs" => [
        "aps" => [
            "terms" => [
                "field" => "identifier",
                "size" => 10000
              ],
            "aggs" => [
                "download_upload" => [
                    "date_histogram" => [
                        "field" => "date",
                        "interval" => "day",
                        "format" => "yyyy-MM-dd",
                        "order" => ["_key" => "asc"]
                    ],
                    "aggs" => [
                        "download" => [
                            "sum" => [
                                "field" => $downloadField
                            ]
                        ],
                        "upload" => [
                            "sum" => [
                                "field" => $uploadField
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]
];

$details = $elastic->search("report", $search, "report_download_upload_all");

if (!$details) {
    $output->writeln("<comment>Não há dados.</comment>");
    exit;
}

$results = $details["aggregations"]["aps"]["buckets"];
$content = "";

foreach ($results as $accessPointData) {
    if ($accessPointData["doc_count"] == 0) {
        continue;
    }

    if ($content == "") {
        $content = "Ponto de Acesso;Data;Upload;Download;\n";
    }

    foreach ($accessPointData["download_upload"]["buckets"] as $uploadDownloadData) {
        $content .= "{$accessPointData['key']};{$uploadDownloadData["key_as_string"]};" .
            convertByteToGBorMB($uploadDownloadData['upload']['value']) . ";" .
            convertByteToGBorMB($uploadDownloadData['download']['value']) . "\n";
    }
}

if ($content != '') {
    try {
        $file = @fopen("UploadDownload.csv", 'w+');
        @fwrite($file, $content);
        @fclose($file);
    } catch (\Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException $exception) {
        $output->writeln("<comment>Problemas ao gerar arquivo.</comment>");
    }
}

function convertByteToGBorMB($bytes)
{
    $mb = ($bytes / 1024 / 1024);

    if ($mb >= 100000000) {
        $result = number_format(($mb/1024/1024/1024), 0, '.', '').' PB';
    } else if ($mb >= 1000000 && $mb <= 100000000) {
        $result = number_format(($mb/1024/1024), 0, '.', '').' TB';
    } else if ($mb >= 1024 && $mb <= 1000000) {
        $result = number_format(($mb/1024), 0, '.', '').' GB';
    } else if (substr($mb, 0, 1) != 0) {
        $result = number_format($mb, 0, '.', '').' MB';
    } else {
        $result = number_format($mb, 2, '.', '').' MB';
    }

    return $result;
}