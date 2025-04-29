<?php

require_once __DIR__ . '/../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);

$smsBillingByDateRange = $application->getKernel()->getContainer()->get('core.service.sms_billing_by_month');

$input  = new \Symfony\Component\Console\Input\ArgvInput([]);
$output = new \Symfony\Component\Console\Output\ConsoleOutput();

$smsBillingByDateRange->execute($input, $output);