<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once __DIR__ . "/../../../../../app/bootstrap.php.cache";
require_once __DIR__ . "/../../../../../app/AppKernel.php";

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Module;
use Wideti\DomainBundle\Repository\ClientRepository;
use Wideti\DomainBundle\Repository\ModuleRepository;

$kernel = new AppKernel("prod", true);
$kernel->boot();

$application    = new Application($kernel);
$container      = $application->getKernel()->getContainer();
$output         = new ConsoleOutput();

/**
 * @var EntityManager $em
 */
$em = $container->get("doctrine")->getEntityManager("default");

/**
 * @var ClientRepository $clientRepo
 */
$clientRepo = $em->getRepository("DomainBundle:Client");

/**
 * @var ModuleRepository $moduleRepo
 */
$moduleRepo = $em->getRepository("DomainBundle:Module");
$clients = $clientRepo->findBy([], [
    "domain" => "asc"
]);

/**
 * @var Client $client
 */
foreach ($clients as $client) {
    $output->writeln("Processing client: {$client->getDomain()}");
    /**
     * @var Module $module
     */
    $module = $moduleRepo->findOneBy([
        'shortCode' => 'sms_marketing'
    ]);
    $client->addModule($module);

    try {
        $em->persist($client);
        $em->flush();
        $output->writeln(">>> DONE");
    }catch (Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex) {
        $output->writeln(">>> SKIP");
    }

    $output->writeln("------------------------------------------------");

}
