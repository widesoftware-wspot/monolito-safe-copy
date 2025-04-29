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
            "filtered" => [
                "filter" => [
                    "bool" => [
                        "must" => [
                            [
                                "range" => [
                                    "acctstarttime" => [
                                        "gte" => "2017-02-22 10:00:00",
                                        "lte" => "2017-02-22 23:59:59"
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ],
    'wspot_2017_02'
);

$count = 0;
$elasticObject = [];

if ($accountings['hits']['total'] > 0) {
    foreach ($accountings['hits']['hits'] as $accounting) {
        $acct = $accounting['_source'];
        $calledstationName = $acct['calledstation_name'];

        if (empty($calledstationName)) {
            continue;
        }

        if (preg_match('/^([a-fA-F0-9]{2}\-){5}[a-fA-F0-9]{2}$/', $calledstationName)) {
            continue;
        }

        if (preg_match('/^([a-fA-F0-9]{2}\-){5}[a-fA-F0-9]{2}/', $calledstationName)) {
            $count++;
            $calledstationName = substr($calledstationName, 0, 17);

            array_push(
                $elasticObject,
                [
                    'update' => [
                        '_index' => 'wspot_2017_02',
                        '_type'  => 'radacct',
                        '_id'    => $accounting['_id']
                    ]
                ]
            );

            array_push(
                $elasticObject,
                [
                    'doc' => [
                        'calledstation_name' => $calledstationName
                    ]
                ]
            );

//            echo 'antes: ' . $acct['calledstation_name'] . ' ---- depois: ' . $calledstationName, $elasticObject; die;
        }
    }

    if (!empty($elasticObject)) {
        $elastic->bulk('radacct', $elasticObject);
    }
}

$output->writeln("<info>".$count." total de accountings corrigidos</info>");

