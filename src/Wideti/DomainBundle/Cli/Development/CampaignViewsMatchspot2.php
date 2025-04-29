<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once __DIR__ . "/../../../../../app/bootstrap.php.cache";
require_once __DIR__ . "/../../../../../app/AppKernel.php";

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;

$kernel = new AppKernel("prod", true);
$kernel->boot();

$application    = new Application($kernel);
$container      = $application->getKernel()->getContainer();
$output         = new ConsoleOutput();
$em             = $container->get("doctrine")->getEntityManager("default");

$campaignId = $argv[1];
$campaign   = $em->getRepository("DomainBundle:Campaign")->findOneById($campaignId);

$ranges = [
	['month' => 'jul_2018_1', 'from' => '2018-07-01 00:00:00', 'to' => '2018-01-06 23:59:59'],
	['month' => 'jul_2018_2', 'from' => '2018-07-07 00:00:00', 'to' => '2018-07-12 23:59:59'],
	['month' => 'jul_2018_3', 'from' => '2018-07-13 00:00:00', 'to' => '2018-07-18 23:59:59'],
	['month' => 'jul_2018_4', 'from' => '2018-07-19 00:00:00', 'to' => '2018-07-24 23:59:59'],
	['month' => 'jul_2018_5', 'from' => '2018-07-25 00:00:00', 'to' => '2018-08-01 00:00:00'],

	['month' => 'ago_2018_1', 'from' => '2018-08-01 00:00:00', 'to' => '2018-08-06 23:59:59'],
	['month' => 'ago_2018_2', 'from' => '2018-08-07 00:00:00', 'to' => '2018-08-12 23:59:59'],
	['month' => 'ago_2018_3', 'from' => '2018-08-13 00:00:00', 'to' => '2018-08-18 23:59:59'],
	['month' => 'ago_2018_4', 'from' => '2018-08-19 00:00:00', 'to' => '2018-08-24 23:59:59'],
	['month' => 'ago_2018_5', 'from' => '2018-08-25 00:00:00', 'to' => '2018-09-01 00:00:00'],

	['month' => 'set_2018_1', 'from' => '2018-09-01 00:00:00', 'to' => '2018-09-06 23:59:59'],
	['month' => 'set_2018_2', 'from' => '2018-09-07 00:00:00', 'to' => '2018-09-12 23:59:59'],
	['month' => 'set_2018_3', 'from' => '2018-09-13 00:00:00', 'to' => '2018-09-18 23:59:59'],
	['month' => 'set_2018_4', 'from' => '2018-09-19 00:00:00', 'to' => '2018-09-24 23:59:59'],
	['month' => 'set_2018_3', 'from' => '2018-09-25 00:00:00', 'to' => '2018-10-01 00:00:00'],

	['month' => 'out_2018_1', 'from' => '2018-10-01 00:00:00', 'to' => '2018-10-06 23:59:59'],
	['month' => 'out_2018_2', 'from' => '2018-10-07 00:00:00', 'to' => '2018-10-12 23:59:59'],
	['month' => 'out_2018_3', 'from' => '2018-10-13 00:00:00', 'to' => '2018-10-18 23:59:59'],
	['month' => 'out_2018_4', 'from' => '2018-10-19 00:00:00', 'to' => '2018-10-24 23:59:59'],
	['month' => 'out_2018_5', 'from' => '2018-10-25 00:00:00', 'to' => '2018-11-01 00:00:00'],

	['month' => 'nov_2018_1', 'from' => '2018-11-01 00:00:00', 'to' => '2018-11-06 23:59:59'],
	['month' => 'nov_2018_2', 'from' => '2018-11-07 00:00:00', 'to' => '2018-11-12 23:59:59'],
	['month' => 'nov_2018_3', 'from' => '2018-11-13 00:00:00', 'to' => '2018-11-18 23:59:59'],
	['month' => 'nov_2018_4', 'from' => '2018-11-19 00:00:00', 'to' => '2018-11-24 23:59:59'],
	['month' => 'nov_2018_5', 'from' => '2018-11-25 00:00:00', 'to' => '2018-12-01 00:00:00'],

	['month' => 'dez_2018_1', 'from' => '2018-12-01 00:00:00', 'to' => '2018-12-06 23:59:59'],
	['month' => 'dez_2018_2', 'from' => '2018-12-07 00:00:00', 'to' => '2018-12-12 23:59:59'],
	['month' => 'dez_2018_3', 'from' => '2018-12-13 00:00:00', 'to' => '2018-12-18 23:59:59'],
	['month' => 'dez_2018_4', 'from' => '2018-12-19 00:00:00', 'to' => '2018-12-24 23:59:59'],
	['month' => 'dez_2018_5', 'from' => '2018-12-25 00:00:00', 'to' => '2019-01-01 00:00:00'],

	['month' => 'jan_2019_1', 'from' => '2019-01-01 00:00:00', 'to' => '2019-01-06 23:59:59'],
	['month' => 'jan_2019_2', 'from' => '2019-01-07 00:00:00', 'to' => '2019-01-12 23:59:59'],
	['month' => 'jan_2019_3', 'from' => '2019-01-13 00:00:00', 'to' => '2019-01-18 23:59:59'],
	['month' => 'jan_2019_4', 'from' => '2019-01-19 00:00:00', 'to' => '2019-01-24 23:59:59'],
	['month' => 'jan_2019_5', 'from' => '2019-01-25 00:00:00', 'to' => '2019-02-01 00:00:00'],

	['month' => 'fev_2019_1', 'from' => '2019-02-01 00:00:00', 'to' => '2019-02-06 23:59:59'],
	['month' => 'fev_2019_2', 'from' => '2019-02-07 00:00:00', 'to' => '2019-02-12 23:59:59'],
	['month' => 'fev_2019_3', 'from' => '2019-02-13 00:00:00', 'to' => '2019-02-18 23:59:59'],
	['month' => 'fev_2019_4', 'from' => '2019-02-19 00:00:00', 'to' => '2019-02-24 23:59:59'],
	['month' => 'fev_2019_5', 'from' => '2019-02-25 00:00:00', 'to' => '2019-03-01 00:00:00'],

	['month' => 'mar_2019_1', 'from' => '2019-03-01 00:00:00', 'to' => '2019-03-06 23:59:59'],
	['month' => 'mar_2019_2', 'from' => '2019-03-07 00:00:00', 'to' => '2019-03-12 23:59:59'],
	['month' => 'mar_2019_3', 'from' => '2019-03-13 00:00:00', 'to' => '2019-03-18 23:59:59'],
	['month' => 'mar_2019_4', 'from' => '2019-03-19 00:00:00', 'to' => '2019-03-24 23:59:59'],
	['month' => 'mar_2019_5', 'from' => '2019-03-25 00:00:00', 'to' => '2019-04-01 00:00:00']
];

$output->writeln("<info>Campanha selecionada: {$campaign->getName()}</info>");
$campaignTitle = clean($campaign->getName());

$total = 0;

foreach ($ranges as $range) {
	$views = getViews($em, $campaign->getId(), $range);
	$count = count($views);
	$periodo = $range['month'];
	$output->writeln("<info>periodo: {$periodo}, qtde: {$count}</info>");

	if ($count > 0) {
		$file = @fopen("{$campaignTitle}_{$periodo}.json", "w+");

		foreach ($views as $view) {
			$message = json_encode([
				"id"                    => $view['id'],
				"campaignId"            => $view['campaign_id'],
				"type"                  => $view['type'] == 1 ? "PRE_LOGIN" : "POS_LOGIN",
				"timestamp"             => $view['view_time'],
				"timeZone"              => "America/Sao_Paulo",
				"guestId"               => $view['guest_id'],
				"guestMacAddress"       => $view['guest'],
				"accessPointIdentifier" => $view['access_point'],
				"domain"                => 'matchspot-hapvida'
			]);

			@fwrite(
				$file,
				"$message\n"
			);
		}

		$total += $count;

		@fclose($file);
	}
}

$output->writeln("<info>TOTAL: {$total}</info>");

function clean($string) {
	$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
	$string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
	$string = strtolower($string);

	return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
}

function getViews($em, $campaignId, $range)
{
	$con        = $em->getConnection();

	$statement  = $con->prepare("
		SELECT *
        FROM campaign_views
        WHERE campaign_id = {$campaignId}
        AND view_time >= '{$range['from']}'
        AND view_time <= '{$range['to']}'
    ");

	$statement->execute();
	$views     = $statement->fetchAll();
	$con->close();
	return $views;
}