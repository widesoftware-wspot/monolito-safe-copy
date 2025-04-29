<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Wideti\DomainBundle\Entity\Campaign;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Doctrine\ORM\EntityManager;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application   = new Application($kernel);
$container     = $application->getKernel()->getContainer();
$output        = new \Symfony\Component\Console\Output\ConsoleOutput();
$em            = $container->get('doctrine')->getEntityManager('default');
$connection    = $em->getConnection();
$clients       = getClients($em);
$statusActive  = Campaign::STATUS_ACTIVE;
$statusExpired = Campaign::STATUS_EXPIRED;

foreach ($clients as $client) {
    $output->writeln("<comment>Domain: {$client->getDomain()}</comment>");

    $statement = $connection->prepare("UPDATE campaign SET " .
        "status = {$statusExpired} WHERE end_date < CURDATE() AND status = {$statusActive} " .
        "AND client_id = {$client->getId()}");
    $statement->execute();

    $output->writeln("<comment>Updated campaigns: {$statement->rowCount()}</comment>");
}

/**
 * @param EntityManager $em
 * @return array|\Wideti\DomainBundle\Entity\Client[]
 */
function getClients(EntityManager $em) {
    return $em->getRepository('DomainBundle:Client')->findAll();
}