<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container = $application->getKernel()->getContainer();

$command = $container->get('core.service.delete_inactive_clients');
$command->execute();
