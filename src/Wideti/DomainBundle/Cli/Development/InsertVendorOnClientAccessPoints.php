<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Question\Question;


$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$input      = new \Symfony\Component\Console\Input\ArgvInput([]);
$output     = new \Symfony\Component\Console\Output\ConsoleOutput();
$question   = new \Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper();
$em         = $container->get('doctrine.orm.entity_manager');
$connection = $em->getConnection();
$output->writeln('<comment>==== INICIANDO A ASSOCIAÇÃO DE VENDORS ====</comment>');

$queryVendor = $connection->prepare("SELECT * FROM vendor");
$queryVendor->setFetchMode(PDO::FETCH_CLASS, \Wideti\DomainBundle\Entity\Vendor::class );
$queryVendor->execute();

$vendor = $queryVendor->fetchAll();

$apQuery = $connection->prepare("SELECT * FROM access_points");
$apQuery->setFetchMode(PDO::FETCH_CLASS, \Wideti\DomainBundle\Entity\AccessPoints::class );
$apQuery->execute();

$aps = $apQuery->fetchAll();

foreach ($aps as $ap) {

    $currentVendorQuery = $connection->prepare("SELECT * FROM vendor WHERE vendor.vendor = '{$ap->getVendor()}'");
    $currentVendorQuery->setFetchMode(PDO::FETCH_CLASS, \Wideti\DomainBundle\Entity\Vendor::class );
    $currentVendorQuery->execute();

    $currentVendor = $currentVendorQuery->fetch();

    if (!$currentVendor) {
        continue;
    }
    $updateQuery = $connection->prepare("UPDATE access_points SET vendor_id = {$currentVendor->getId()} WHERE id = {$ap->getId()}");
    $updateQuery->execute();

}
