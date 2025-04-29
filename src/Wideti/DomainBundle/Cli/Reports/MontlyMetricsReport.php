<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$em             = $container->get('doctrine.orm.entity_manager');
$mongo          = $container->get('doctrine.odm.mongodb.document_manager');
$elastic        = $container->get('core.service.elastic_search');
$monolog        = $container->get('logger');
$twig           = $container->get('templating');
$mailer         = $container->get('core.service.mailer');
$emailHeader    = $container->get('core.service.email_header');
$whiteLabel     = $container->get('core.service.white_label');

$clients = $em->getRepository('DomainBundle:Client')->findAll();

$dateFrom = date_format(new \DateTime('NOW -30 days'), 'Y-m-d 00:00:00');
$dateTo   = date_format(new \DateTime('NOW'), 'Y-m-d 23:59:59');

$mongoClient = $mongo->getConnection()->getMongoClient();

$totalClientsUsingBlockPerTime      = 0;
$totalClientsUsingAccessValidity    = 0;
$totalClientsUsingBandwidthLimit    = 0;

$totalAccessRules                   = 0;
$totalBlockPerTimeActive            = 0;
$totalAccessValidityActive          = 0;
$totalBandwidthLimitActive          = 0;

$totalAuthentications               = 0;
$totalGuests                        = 0;

foreach ($clients as $client) {
    $blockPerTimeActive     = false;
    $accessValidityActive   = false;
    $bandwidthLimitActive   = false;

    $clientDatabase     = $client->getDomain();
    $clientDatabase     = \Wideti\DomainBundle\Helpers\StringHelper::slugDomain($clientDatabase);
    $database           = $mongoClient->$clientDatabase;
    $groupsCollection   = $database->groups;
    $guestsCollection   = $database->guests;

    $totalAuthentications += $guestsCollection->count([
        'lastAccess' => [
            '$gte' => new \MongoDate(strtotime($dateFrom)),
            '$lte' => new \MongoDate(strtotime($dateTo))
        ]
    ]);

    $totalGuests += $guestsCollection->count([
        'created' => [
            '$gte' => new \MongoDate(strtotime($dateFrom)),
            '$lte' => new \MongoDate(strtotime($dateTo))
        ]
    ]);

    $totalAccessRules += $groupsCollection->count([
        '$and'=> [
            [
                'shortcode' => [
                    '$ne' => 'guest'
                ]
            ],
            [
                'shortcode' => [
                    '$ne' => 'employee'
                ]
            ]
        ]
    ]);

    $groups = $groupsCollection->find();

    foreach ($groups as $group) {
        foreach ($group['configurations'] as $configs) {
            foreach ($configs['configurationValues'] as $values) {
                if ($values['key'] == 'enable_block_per_time' && $values['value'] == 1) {
                    $totalBlockPerTimeActive++;
                    $blockPerTimeActive = true;
                }
                if ($values['key'] == 'enable_validity_access' && $values['value'] == 1) {
                    $totalAccessValidityActive++;
                    $accessValidityActive = true;
                }
                if ($values['key'] == 'enable_bandwidth' && $values['value'] == 1) {
                    $totalBandwidthLimitActive++;
                    $bandwidthLimitActive = true;
                }
            }
        }
    }

    if ($blockPerTimeActive) {
        $totalClientsUsingBlockPerTime++;
    }

    if ($accessValidityActive) {
        $totalClientsUsingAccessValidity++;
    }

    if ($bandwidthLimitActive) {
        $totalClientsUsingBandwidthLimit++;
    }
}

$totalAccountings = $result = $elastic->search(
    'radacct',
    [
        "size" => 0,
        "query" => [
            "bool" => [
                "must" => [
                    [
                        "range" => [
                            "acctstarttime" => [
                                "gte" => $dateFrom,
                                "lte" => $dateTo
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ],
    \Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch::LAST_MONTH
);

$html = $twig->render(
    'DomainBundle:MailReport:montlyMetricsReport.html.twig',
    [
        'totalClientsUsingBlockPerTime'     => $totalClientsUsingBlockPerTime,
        'totalClientsUsingAccessValidity'   => $totalClientsUsingAccessValidity,
        'totalClientsUsingBandwidthLimit'   => $totalClientsUsingBandwidthLimit,

        'totalAccessRules'                  => $totalAccessRules,
        'totalBlockPerTimeActive'           => $totalBlockPerTimeActive,
        'totalAccessValidityActive'         => $totalAccessValidityActive,
        'totalBandwidthLimitActive'         => $totalBandwidthLimitActive,

        'totalAccountings'                  => $totalAccountings['hits']['total'],
        'totalAuthentications'              => $totalAuthentications,
        'totalGuests'                       => $totalGuests,
        'whiteLabel'                        => $whiteLabel->getDefaultWhiteLabel()
    ]
);

$builder = new \Wideti\DomainBundle\Service\Mailer\Message\MailMessageBuilder();
$message = $builder
    ->subject('Métricas Mensal WSpot')
    ->from(['WSpot' => $emailHeader->getSender()])
    ->to([
        [
            'guilherme.rogieri@widesoftware.com.br'
        ],
        [
            'leonardo.fuzeto@wspot.com.br'
        ]
    ])
    ->htmlMessage($html)
    ->build()
;

$mailer->send($message);
