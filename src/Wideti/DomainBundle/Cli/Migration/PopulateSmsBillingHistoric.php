<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Wideti\DomainBundle\Entity\SmsBillingHistoric;
use Wideti\DomainBundle\Helpers\DateTimeHelper;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container   = $application->getKernel()->getContainer();

$input      = new \Symfony\Component\Console\Input\ArgvInput([]);
$output     = new \Symfony\Component\Console\Output\ConsoleOutput();

$em         = $container->get('doctrine.orm.entity_manager');

$clients    = $em->getRepository('DomainBundle:Client')
    ->findAll()
;

if (count($clients) == 0) {
    $output->writeln("<comment>Nenhum cliente encontrado.</comment>");
}

$output->writeln("<info>".count($clients)." clientes encontrados</info>");

$progressBar = new \Symfony\Component\Console\Helper\ProgressBar($output, count($clients));
$progressBar->setBarCharacter('<fg=magenta>=</>');
$progressBar->setProgressCharacter("|");

foreach ($clients as $client) {
    $closingDate    = $client->getClosingDate();
    $today          = date('d');

    if ($closingDate >= $today) {
        $dateFrom   = date_format(new \DateTime('now-2month'), 'Y-m-');
        $dateTo     = date_format(new \DateTime('now-1month'), 'Y-m-');
    } else {
        $dateFrom   = date_format(new \DateTime('now-1month'), 'Y-m-');
        $dateTo     = date_format(new \DateTime('now'), 'Y-m-');
    }
    $dateFrom       = $dateFrom.DateTimeHelper::formatHour($closingDate).' 00:00:00';
    $dateTo         = strtotime($dateTo.DateTimeHelper::formatHour($closingDate));
    $dateTo         = new \DateTime(date('Y-m-d', $dateTo));
    $billingDate    = $dateTo;

    $dateTo         = $dateTo->sub(new DateInterval('P1D'));
    $dateTo         = $dateTo->format('Y-m-d 23:59:59');

    $smsBilling     = $em
        ->getRepository('DomainBundle:SmsHistoric')
        ->getTotalToSmsBillingHistoric($client, $dateFrom, $dateTo);

    $historic       = $smsBilling[0];
    $totalCost      = str_replace(',', '.', $historic['unit_price']) * $historic['total'];

    $smsBillingHistoric = new SmsBillingHistoric();
    $smsBillingHistoric->setClient($client);
    $smsBillingHistoric->setTotalSent((int) $historic['total']);
    $smsBillingHistoric->setTotalCost($totalCost);
    $smsBillingHistoric->setDate($billingDate->add(new DateInterval('P1D')));

    $em->persist($smsBillingHistoric);
    $em->flush();

    $progressBar->advance();
}

$progressBar->finish();
$output->writeln("");

$output->writeln("<comment>-- fim da execução --</comment>");