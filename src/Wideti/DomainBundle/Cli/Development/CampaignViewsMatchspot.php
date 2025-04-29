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

$client     = $em->getRepository("DomainBundle:Client")->findOneByDomain('matchspot');

$campaigns  = $em->getRepository("DomainBundle:Campaign")->findBy([
	'client' => $client
]);

$ranges = [
	['month' => 'jan_2019', 'from' => '2019-01-01 00:00:00', 'to' => '2019-02-01 00:00:00'],
	['month' => 'fev_2019', 'from' => '2019-02-01 00:00:00', 'to' => '2019-03-01 00:00:00'],
	['month' => 'mar_2019', 'from' => '2019-03-01 00:00:00', 'to' => '2019-04-01 00:00:00']
];

/**
 * @var $campaign \Wideti\DomainBundle\Entity\Campaign
 */
foreach ($campaigns as $campaign) {
	$output->writeln("<info>Campanha selecionada: {$campaign->getName()}</info>");
	$campaignTitle = clean($campaign->getName());

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
					"domain"                => $client->getDomain()
				]);

				@fwrite(
					$file,
					"$message\n"
				);
			}

			@fclose($file);
		}
	}

	echo "\n";
}

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