<?php

require_once __DIR__ . '/../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);

$registerAccessPoints = $application->getKernel()->getContainer()->get('core.service.register_access_points');

$registerAccessPoints->init();
