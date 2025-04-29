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
$mongo      = $container->get('doctrine.odm.mongodb.document_manager');

$em         = $container->get('doctrine')->getEntityManager('default');
$con        = $em->getConnection();

if (!array_key_exists(1, $argv)) {
	$output->writeln("<comment>Informe o domínio do cliente.</comment>");
	exit;
}

if (!array_key_exists(2, $argv) || !array_key_exists(3, $argv)) {
	$output->writeln("<comment>Informe o range (data inicio, data fim) de data no formato: 2019-01-01.</comment>");
	exit;
}

$domain     = $argv[1];
$dateFrom   = $argv[2];
$dateTo     = $argv[3];

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

$exportFile = [];
$file       = $domain . "_cadastros_por_dia.csv";
$outfile    = "$file";

$fp = fopen($outfile, "wb");

array_push($exportFile, "Data;Total");

$mongoClient    = $mongo->getConnection()->getMongoClient();
$clientDatabase = StringHelper::slugDomain($client->getDomain());
$database       = $mongoClient->$clientDatabase;
$collection     = $database->guests;

$search = [
    'created' => [
        '$gte' => new MongoDate(strtotime("{$dateFrom} 00:00:00")),
        '$lte' => new MongoDate(strtotime("{$dateTo} 23:59:59"))
    ]
];

$guests     = $collection->find($search);
$cadastros  = [];
$value      = 0;

if ($guests->count() > 0) {
	$emptyFile = false;

	foreach ($guests as $guest) {
		$key = date("d/m/Y", $guest['created']->sec);
		$value++;
		$cadastros[$key][] = $guest['mysql'];
	}

	foreach ($cadastros as $key=>$value) {
		$qtde = count($value);

		array_push(
			$exportFile,
			$key .";".
			$qtde
		);
	}

    foreach ($exportFile as $item) {
        @fwrite($fp, $item . "\n");
    }

    @fclose($fp);
}
