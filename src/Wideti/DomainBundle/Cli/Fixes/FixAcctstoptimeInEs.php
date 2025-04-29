<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$output     = new \Symfony\Component\Console\Output\ConsoleOutput();
$em         = $container->get('doctrine.orm.entity_manager');
$elastic    = $container->get('core.service.elastic_search');

$accountings = $elastic->search(
    'radacct',
    [
        "size" => 999999,
        "query" => [
            "filtered" => [
                "filter" => [
                    "bool" => [
                        "must" => [
                            [
                                "missing" => [
                                    "field" => "acctstoptime"
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]
);

$count = 0;

if ($accountings['hits']['total'] > 0) {

    foreach ($accountings['hits']['hits'] as $accounting) {

        $count++;

        $mysql = $em->getRepository('DomainBundle:Radacct')
            ->findOneBy([
                'acctuniqueid' => $accounting['_source']['acctuniqueid']
            ]);

        if ($mysql) {
            $acctstoptime = date_format($mysql->getAcctstoptime(), 'Y-m-d H:i:s');

            $elastic->update('radacct', $accounting['_id'], [
                'doc' => [
                    'acctstoptime' => $acctstoptime
                ]
            ]);
        }

    }

}

$output->writeln("<info>".$count." total de accountings corrigidos</info>");
