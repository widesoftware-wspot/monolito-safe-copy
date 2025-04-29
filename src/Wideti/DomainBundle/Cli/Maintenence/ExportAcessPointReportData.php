<?php
// php5.6 ExportAccessPointReportData.php 1 01/01/2017 04/01/2017
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once __DIR__ . "/../../../../../app/bootstrap.php.cache";
require_once __DIR__ . "/../../../../../app/AppKernel.php";

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Wideti\DomainBundle\Repository\Elasticsearch\Report\ReportRepository;
use Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch;

$kernel = new AppKernel("prod", true);
$kernel->boot();

$application       = new Application($kernel);
$container         = $application->getKernel()->getContainer();
$output            = new ConsoleOutput();
$em                = $container->get("doctrine")->getEntityManager("default");
$reportRepository  = new ReportRepository(new ElasticSearch($container->getParameter("elastic_hosts")));

if (!isset($argv[1])) {
    $output->writeln("<comment>ID do cliente deve ser informado.</comment>");
    exit;
}

$client = $em->getRepository("DomainBundle:Client")->find($argv[1]);

if (!$client) {
    $output->writeln("<comment>ID do cliente não foi encontrado na base de dados.</comment>");
    exit;
}
$accessPoint  = [];
$accessPoints = $em->getRepository("DomainBundle:AccessPoints")
    ->findBy(["client" => $client->getId()]);



if ($accessPoints) {
    if (isset($argv[2])) {
        $dateFrom = date("Y-m-d", strtotime(str_replace("/", "-", $argv[2])));
    } else {
        $dateFrom = new \DateTime("NOW -30 days");
        $dateFrom = $dateFrom->format("Y-m-d");
    }

    if (isset($argv[3])) {
        $dateTo = date("Y-m-d", strtotime(str_replace("/", "-", $argv[3])));
    } else {
        $dateTo = new \DateTime("NOW");
        $dateTo = $dateTo->format("Y-m-d");
    }

    $accessPoint = [];

    foreach ($accessPoints as $aps) {
        @array_push($accessPoint, ["term" => ["friendlyName" => $aps->getFriendlyName()]]);
    }

    $result = $reportRepository->getVisitsAndRecordsPerAccessPoint(
        $client,
        ["date_from" => $dateFrom, "date_to" => $dateTo],
        $accessPoint,
        count($accessPoint)
    );

    if ($result) {
        $file = @fopen("{$client->getDomain()}_apData.csv", "w+");

        @fwrite($file, "Ponto de Acesso;Total de Visitas;Total de Cadastros;\n");

        foreach ($result as $data) {
            @fwrite(
                $file,
                "{$data["key"]};{$data["totalVisits"]["value"]};{$data["totalRegistrations"]["value"]};\n"
            );
        }

        @fclose($file);
    }
}