<?php
require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Wideti\DomainBundle\Helpers\StringHelper;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container   = $application->getKernel()->getContainer();

$em         = $container->get('doctrine.orm.entity_manager');
$mongo      = $container->get('doctrine.odm.mongodb.document_manager');

$clients = $em->getRepository("DomainBundle:Client")->findAll();

$total = 0;

foreach ($clients as $client) {
	$mongoClient = $mongo->getConnection()->getMongoClient();
	$clientDatabase = StringHelper::slugDomain($client->getDomain());
	$database = $mongoClient->$clientDatabase;
	$collection = $database->guests;

	$social = $collection->find([
		'registerMode' => [
			'$in' => ["Facebook", "Twitter", "Instagram", "Google", "LinkedIn"]
		]
	]);

	$total += $social->count();
}

echo "TOTAL: {$total}";
