<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);

$container  = $application->getKernel()->getContainer();
$output     = new \Symfony\Component\Console\Output\ConsoleOutput();
$mongo      = $container->get('doctrine.odm.mongodb.document_manager');
$em         = $container->get('doctrine')->getEntityManager('default');

if (!isset($argv[1])) {
    $output->writeln("<info>The e-mail account, which will be deleted, must be informed!</info>");
    exit;
}

$removeUser = $argv[1];
$clients    = $em->getRepository('DomainBundle:Client')->findAll();

foreach ($clients as $client) {
    $em->getRepository('DomainBundle:Users')->delete($client->getId(), $removeUser);
    $output->writeln("<info>User contato@wideti.com.br was successfully deleted! Client ({$client->getDomain()}).</info>");
}