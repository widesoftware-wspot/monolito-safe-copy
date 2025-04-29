
<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application    = new Application($kernel);
$container      = $application->getKernel()->getContainer();
$output         = new \Symfony\Component\Console\Output\ConsoleOutput();
$command        = $container->get('core.service.aggregate_campaign_views');

if (!isset($argv[1]) || !isset($argv[2])) {
    $output->writeln("<error>Obrigatório informar a data de inicio e fim como argumento, ex: '0000-00-00 00:00:00'</error>");
    exit;
}

$argDateFrom = $argv[1];
$argDateTo   = $argv[2];

$startDate  = new DateTime($argDateFrom);
$endDate    = new DateTime($argDateTo);
$total      = $endDate->diff($startDate)->format("%a");

$interval   = DateInterval::createFromDateString('1 day');
$period     = new DatePeriod($startDate, $interval, $endDate);

$progressBar = new \Symfony\Component\Console\Helper\ProgressBar($output, $total);
$progressBar->setBarCharacter('<fg=magenta>=</>');
$progressBar->setProgressCharacter("|");

$output->writeln("<info>Processando o total de {$total} dias</info>");

$clientId = null;

if (isset($argv[3])) {
    $clientId = $argv[3];
}

/**
 * @var DateTime $dt
 */
foreach ($period as $dt) {
    $dateFrom   = clone $dt->setTime(0, 0, 0);
    $dateTo     = clone $dt->setTime(23, 59, 59);

    $command->execute($dateFrom, $dateTo, $clientId);
    $progressBar->advance();
}
