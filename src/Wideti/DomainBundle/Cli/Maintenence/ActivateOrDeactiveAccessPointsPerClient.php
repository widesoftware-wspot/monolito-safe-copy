<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container   = $application->getKernel()->getContainer();
$output      = new ConsoleOutput();
$em          = $container->get('doctrine')->getEntityManager('default');

if (!isset($argv[1])) {
    $output->writeln("<comment>ID do cliente deve ser informado.</comment>");
    exit;
}

if (!isset($argv[2])) {
    $output->writeln("<comment>Operação deve ser informada: [activate/deactivate].</comment>");
    exit;
} else {
    $operations = ["activate" => 1, "deactivate" => 0];

    if (!isset($operations[$argv[2]])) {
        $output->writeln("<comment>Operação deve ser: [activate/deactivate].</comment>");
        exit;
    }
}

$clientId  = $argv[1];
$operation = $argv[2];

$connection = $em->getConnection();
$statement  = $connection->prepare("UPDATE access_points SET status = {$operations[$argv[2]]} " .
    "WHERE client_id = {$clientId}");

$statement->execute();

$output->writeln("{$statement->rowCount()} pontos de acesso foram atualizados");