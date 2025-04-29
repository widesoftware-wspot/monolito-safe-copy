<?php

require_once __DIR__ . '/../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Wideti\DomainBundle\Entity\AccessPoints;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application    = new Application($kernel);
$container      = $application->getKernel()->getContainer();

$output         = new \Symfony\Component\Console\Output\ConsoleOutput();
$em             = $container->get('doctrine.orm.entity_manager');
$elastic        = $container->get('core.service.elastic_search');

$dateLimit = new \DateTime();
$dateLimit->sub(new \DateInterval('P15D'));
$dateLimit->setTime(0,0,0);

$qb = $em->getRepository('DomainBundle:AccessPoints')->createQueryBuilder('ap');
$qb->select('ap')
    ->where('ap.created < :dateLimit')
    ->andWhere('ap.status = :status')
    ->setParameter('dateLimit', $dateLimit->format('Y-m-d H:i:s'))
    ->setParameter('status', 1);

/**
 * @var AccessPoints[] $accessPoints
 */
$accessPoints = $qb->getQuery()->getResult();

$progressBar = new \Symfony\Component\Console\Helper\ProgressBar($output, count($accessPoints));
$progressBar->setBarCharacter('<fg=magenta>=</>');
$progressBar->setProgressCharacter("|");

foreach ($accessPoints as $ap) {
    $query = [
        'size' => 0,
        'query' => [
            'term' => [
                'calledstation_name' => $ap->getFriendlyName()
            ]
        ]
    ];

    $result = $elastic->search(
        'radacct',
        $query,
        \Wideti\DomainBundle\Service\ElasticSearch\ElasticSearch::LAST_3_MONTHS
    );

    if ($result['hits']['total'] == 0) {
        $ap->setStatus(\Wideti\DomainBundle\Entity\AccessPoints::INACTIVE);
        $em->persist($ap);
        $em->flush();
    }

    $progressBar->advance();
}

$progressBar->finish();
$output->writeln("");
$output->writeln("<comment>-- fim --</comment>");
