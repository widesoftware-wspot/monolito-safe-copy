<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Question\Question;
use Wideti\DomainBundle\Helpers\StringHelper;

$kernel = new AppKernel('dev', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$input      = new \Symfony\Component\Console\Input\ArgvInput([]);
$output     = new \Symfony\Component\Console\Output\ConsoleOutput();
$question   = new \Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper();
$em         = $container->get('doctrine.orm.entity_manager');
$mongo      = $container->get('doctrine.odm.mongodb.document_manager');
$elastic      = $container->get('core.service.elastic_search');


if (isset($argv[1]) && $argv[1] == "init"){
    $totalAccountings = 100;
    $totalMonths = 2;
    $domain = "dev";
} else {
    $output->writeln('<comment>==== Gerador de accouting ====</comment>');
    $domain = $question->ask($input, $output, new Question('<info>Digite o domínio do cliente (string): </info>', null));
    $totalAccountings = $question->ask($input, $output, new Question('<info>Total acct por visitante (int): </info>', null));
    $totalMonths = $question->ask($input, $output, new Question('<info>Qtd. meses a partir de hoje (int): </info>', null));
}

$client = $em->getRepository('DomainBundle:Client')->findOneBy([
    'domain' => $domain
]);

if (empty($client)) {
    $output->writeln("<comment>Cliente \"{$domain}\" não existe</comment>");
    exit;
}

// Pega os access Points
$accessPoints = $em->getRepository('DomainBundle:AccessPoints')->findAll();

// Pega todos visitantes
$mongoClient    = $mongo->getConnection()->getMongoClient();
$clientDatabase = StringHelper::slugDomain($client->getDomain());
$databaseMongo   = $mongoClient->$clientDatabase;
$collection = $databaseMongo->guests;
$guests = $collection->find();

$output->writeln("<comment>Gerando accountings, aguarde pode levar alguns minutos...</comment>");

//gera os accountigns
generateAccounting($elastic, $client, $guests, $accessPoints, $totalAccountings, $totalMonths);

function generateAccounting(
    \Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch $elastic,
    \Wideti\DomainBundle\Entity\Client $client,
    $guests = [],
    $accessPoints = [],
    $limitAccountigns = 100,
    $totalMonths = 12
) {

    foreach ($guests as $guest) {
        $totalOfAccounting = rand(0, $limitAccountigns);

        for ($current = 0; $current <= $totalOfAccounting; $current++) {
            $dataStart = date("Y-m-d H:i:s", mt_rand(strtotime("-" . $totalMonths ." months"), time()));
            $stopTime = new \DateTime($dataStart);
            $stopTime->add(new \DateInterval('PT' . rand(10, 120) . "M"));
            $stopTimeFormat = $stopTime->format("Y-m-d H:i:s");
            $accessPointIndex = rand(0, count($accessPoints)-1);

            $acct = [
                "id"                    => uniqid(),
                "client_id"             => $client->getId(),
                "acctsessionid"         => rand() * 7,
                "acctuniqueid"          => uniqid(),
		"username"              => $guest['mysql'],
		"employee"		=> isset($guest['group']) && $guest['group'] == "employee" ? true : false,
                "nasipaddress"          => "192.168.150.251",
                "acctstarttime"         => $dataStart,
                "acctstoptime"          => $stopTimeFormat,
	            "acctinputoctets"       => rand(0, 30000000),
	            "acctoutputoctets"      => rand(0, 30000000),
	            "download"              => rand(0, 30000000),
	            "upload"                => rand(0, 30000000),
                "calledstationid"       => $accessPoints[$accessPointIndex]->getIdentifier(),
                "calledstation_name"    => $accessPoints[$accessPointIndex]->getFriendlyName(),
                "callingstationid"      => isset($guest['accessData']) ? $guest['accessData'][0]["macaddress"] : generateMacAddress() ,
                "framedipaddress"       => "192.168.100.253",
                "interim_update"        =>  $stopTimeFormat
            ];

            $elastic->index('radacct', json_encode($acct), $acct['id'], "wspot_" . date("Y_m", strtotime($dataStart)));
        }
    }
}


function generateMacAddress()
{
    return strtoupper(implode('-',str_split(str_pad(base_convert(mt_rand(0,0xffffff),10,16).base_convert(mt_rand(0,0xffffff),10,16),12),2)));
}
