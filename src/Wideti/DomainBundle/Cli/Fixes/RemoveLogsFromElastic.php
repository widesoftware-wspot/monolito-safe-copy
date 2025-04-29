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

$from   = $argv[1];
$to     = $argv[2];

do {

    $logs = $elastic->search(
        'changelog',
        [
            "size" => 9999,
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "range" => [
                                "date" => [
                                    "gte" => "{$from} 00:00:00",
                                    "lte" => "{$to} 23:59:59"
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'log'
    );

    $count = 0;
    $elasticObject = [];

    if ($logs['hits']['total'] > 0) {
        foreach ($logs['hits']['hits'] as $log) {
            array_push(
                $elasticObject,
                [
                    'delete' => [
                        '_index' => 'log',
                        '_type'  => 'changelog',
                        '_id'    => $log['_id']
                    ]
                ]
            );
        }

        if (!empty($elasticObject)) {
            $elastic->bulk('changelog', $elasticObject);
        }
    }

    $count = $logs['hits']['total'];
    $output->writeln("<info>".$count." logs ainda existem</info>");
} while ($count > 0);