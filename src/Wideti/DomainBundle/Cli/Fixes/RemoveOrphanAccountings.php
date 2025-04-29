<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$output     = new \Symfony\Component\Console\Output\ConsoleOutput();
$elastic    = $container->get('core.service.elastic_search');

$accountings = $elastic->search(
    'radacct',
    [
        "size" => 9999,
        "query" => [
            "bool" => [
                "must" => [
                    [
                        "term" => [
                            "client_id" => 13491
                        ]
                    ],
                    [
                        "term" => [
                            "username" => 21063393
                        ]
                    ],
                    [
                        "term" => [
                            "framedipaddress" => "10.97.0.42"
                        ]
                    ]
                ]
            ]
        ]
    ],
    'wspot_2019_10'
);

$count = 0;
$elasticObject = [];

if ($accountings['hits']['total'] > 0) {
    foreach ($accountings['hits']['hits'] as $accounting) {
        array_push(
            $elasticObject,
            [
                'delete' => [
                    '_index' => 'wspot_2019_10',
                    '_type'  => 'radacct',
                    '_id'    => $accounting['_id']
                ]
            ]
        );
    }

    if (!empty($elasticObject)) {
        $elastic->bulk('radacct', $elasticObject);
    }
}

$count = $accountings['hits']['total'];

$output->writeln("<info>".$count." total de accountings removidos</info>");
