<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Wideti\DomainBundle\Helpers\StringHelper;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$output     = new \Symfony\Component\Console\Output\ConsoleOutput();

$elastic    = $container->get('core.service.elastic_search');
$mongo      = $container->get('doctrine.odm.mongodb.document_manager');
$monolog    = $container->get('logger');

$em         = $container->get('doctrine')->getEntityManager('default');
$con        = $em->getConnection();

$ftp_server = $container->getParameter('ftp_server');
$ftp_user   = $container->getParameter('ftp_user');
$ftp_pass   = $container->getParameter('ftp_pass');

$domain     = $argv[1];
$reportType = $argv[2];

if (array_key_exists(3, $argv)) {
    $date   = $argv[3];
} else {
    $date   = date('Y-m-d', strtotime("-1 days"));
}

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

$clientId = $client->getId();

if ($reportType == 'acessos') {
    $exportFile = [];
    $file = $domain . "_acessos_" . str_replace('-', '', $date) . ".csv";
    $outfile = "$file";

    $params = [
        "scroll"    => "60s",
        "size"      => 1000,
        "index"     => "all",
        "type"      => "radacct",
        "body"      => [
            "query" => [
                "query" => [
                    "filtered" => [
                        "filter" => [
                            "and" => [
                                "filters" => [
                                    [
                                        "term" => [
                                            "client_id" => $clientId
                                        ]
                                    ],
                                    [
                                        "range" => [
                                            "acctstarttime" => [
                                                "gte" => $date . " 00:00:00",
                                                "lte" => $date . " 23:59:59"
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    $response   = $elastic->searchScroll($params);
    $scroll_id  = $response['_scroll_id'];

    if ($response['hits']['total'] > 0) {
        $emptyFile = false;

        $fp = fopen($outfile, "wb");
        array_push($exportFile, "email;acctstarttime;acctstoptime;framedipaddress;calledstation_name");

        $mongoClient    = $mongo->getConnection()->getMongoClient();
        $clientDatabase = StringHelper::slugDomain($client->getDomain());
        $database       = $mongoClient->$clientDatabase;
        $collection     = $database->guests;

        while (\true) {
            foreach ($response['hits']['hits'] as $access) {
                $guest = $collection->findOne([
                    "mysql" => $access['_source']['username']
                ]);

                $guestEmail          = array_key_exists('email', $guest['properties'])
                    ? $guest['properties']['email'] : '';
                $acctstarttime       = array_key_exists('acctstarttime', $access['_source'])
                    ? $access['_source']['acctstarttime'] : '';
                $acctstoptime        = array_key_exists('acctstoptime', $access['_source'])
                    ? $access['_source']['acctstoptime'] : '';
                $framedipaddress     = array_key_exists('framedipaddress', $access['_source'])
                    ? $access['_source']['framedipaddress'] : '';
                $calledstation_name  = array_key_exists('calledstation_name', $access['_source'])
                    ? $access['_source']['calledstation_name'] : '';

                array_push(
                    $exportFile,
                    $guestEmail .";".
                    $acctstarttime .";".
                    $acctstoptime .";".
                    $framedipaddress .";".
                    $calledstation_name
                );
            }

            $response = $elastic->scroll(
                [
                    "scroll_id" => $scroll_id,
                    "scroll"    => "60s"
                ]
            );

            if (count($response['hits']['hits']) == 0) {
                break;
            }

            $scroll_id = $response['_scroll_id'];
        }

        foreach ($exportFile as $item) {
            @fwrite($fp, $item . "\n");
        }

        @fclose($fp);
    }
}

if ($reportType == 'visitantes') {
    $exportFile = [];
    $file = $domain . "_visitantes_" . str_replace('-', '', $date) . ".csv";
    $outfile = "$file";

    $fp = fopen($outfile, "wb");

    array_push(
        $exportFile,
        "email;nome_completo;tipo_documento;documento;telefone;cep;locale;data_cadastro;ultimo_acesso;status;authorize_email;ponto_de_acesso"
    );

    $mongoClient    = $mongo->getConnection()->getMongoClient();
    $clientDatabase = StringHelper::slugDomain($client->getDomain());
    $database       = $mongoClient->$clientDatabase;
    $collection     = $database->guests;
    $search         = [];

    $search = [
        'created' => [
            '$lte' => new MongoDate(strtotime($date." 23:59:59"))
        ]
    ];

    $guests = $collection->find($search);

    if ($guests->count() > 0) {
        $emptyFile = false;

        foreach ($guests as $guest) {
            $email = $name = $document = $phone = $cep = $authorizeEmail = $registrationMacAddress = "";

            if (array_key_exists('email', $guest['properties'])) {
                $email = $guest['properties']['email'];
            }

            if (array_key_exists('name', $guest['properties'])) {
                $name = $guest['properties']['name'];
            }

            if (array_key_exists('document', $guest['properties'])) {
                $document = $guest['properties']['document'];
            }

            if (array_key_exists('phone', $guest['properties'])) {
                $phone = $guest['properties']['phone'];
            }

            if (array_key_exists('zip_code', $guest['properties'])) {
                $cep = $guest['properties']['zip_code'];
            }

            if (array_key_exists('authorizeEmail', $guest)) {
                $authorizeEmail = $guest['authorizeEmail'];
            }

            if (array_key_exists('registrationMacAddress', $guest)) {
                $registrationMacAddress = $guest['registrationMacAddress'];
            }

            $created    = new \DateTime(date('Y-m-d H:i:s', $guest['created']->sec));
            $lastAccess = (array_key_exists('lastAccess', $guest))
                ? new \DateTime(date('Y-m-d H:i:s', $guest['lastAccess']->sec)) : '';

            if ($lastAccess) {
                $lastAccess = date_format($lastAccess, 'Y-m-d H:i:s');
            }

            array_push(
                $exportFile,
                $email .";".
                $name .";".
                $guest['documentType'] .";".
                $document .";".
                $phone .";".
                $cep .";".
                $guest['locale'] .";".
                date_format($created, 'Y-m-d H:i:s') .";".
                $lastAccess .";".
                $guest['status'] .";".
                $authorizeEmail . ";" .
                $registrationMacAddress
            );
        }

        foreach ($exportFile as $item) {
            @fwrite($fp, $item . "\n");
        }

        @fclose($fp);
    }
}

if (!$emptyFile) {
    $conn_id = ftp_connect($ftp_server) or die("Could not connect to $ftp_server");
    $login = ftp_login($conn_id, $ftp_user, $ftp_pass);

    ftp_pasv($conn_id, true);
    if (!$conn_id || !$login) {
        die('Connection attempt failed!');
    }

    if (ftp_put($conn_id, $file, $outfile, FTP_ASCII)) {
        $monolog->addInfo("Arquivo $file enviado com sucesso!");
    } else {
        $monolog->addCritical("Falha no upload do arquivo $file");
    }

    ftp_close($conn_id);
} else {
    $monolog->addInfo("Não existe arquivo para realizar upload!");
}

unlink($file);
