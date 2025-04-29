<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Wideti\DomainBundle\Helpers\StringHelper;

$kernel = new AppKernel('dev', true);
$kernel->boot();

$application    = new Application($kernel);
$container      = $application->getKernel()->getContainer();

$output         = new \Symfony\Component\Console\Output\ConsoleOutput();
$input          = new \Symfony\Component\Console\Input\ArgvInput();
$em             = $container->get('doctrine.orm.entity_manager');
$mongo          = $container->get('doctrine.odm.mongodb.document_manager');

$client = $em
    ->getRepository('DomainBundle:Client')
    ->findOneBy([
        'domain' => 'dev'
    ]);

$totalGuests = 50;
$progressBar = new \Symfony\Component\Console\Helper\ProgressBar($output, $totalGuests);
$progressBar->setBarCharacter('<fg=magenta>=</>');
$progressBar->setProgressCharacter("|");

$output->writeln("<info>{$totalGuests} visitantes serão inseridos</info>");
$mongoClient    = $mongo->getConnection()->getMongoClient();
$clientDatabase = StringHelper::slugDomain($client->getDomain());
$database       = $mongoClient->$clientDatabase;
$collection     = $database->guests;

for ($i=0; $i<$totalGuests; $i++) {
    $guestMySql = new \Wideti\DomainBundle\Entity\Guests();
    $guestMySql->setClient($client);
    $em->persist($guestMySql);
    $em->flush();

    $document = [
        'mysql'                     => $guestMySql->getId(),
        'password'                  => '123456',
        'group'                     => 'guest',
        'status'                    => \Wideti\DomainBundle\Document\Guest\Guest::STATUS_ACTIVE,
        'emailIsValid'              => false,
        'emailIsValidDate'          => new \MongoDate(),
        'locale'                    => "pt_br",
        'returning'                 => false,
        'registrationMacAddress'    => "API",
        'registerMode'              => "API",
        'created'                   => new \MongoDate(),
        'lastAccess'                => new \MongoDate(),
        'documentType'              => 'CPF',
        'properties'                => [
            'name'  => "Usuário {$i}",
            'email'  => "user_{$i}@wspot.com.br",
            'phone' => '19900000000'
        ]
    ];

    try {
        $collection->insert($document);
    } catch (\Exception $ex) {
        $output->writeln("<error>Falha ao inserir</error>");
        exit;
    }

    $em->detach($guestMySql);
    $em->getConnection()->getConfiguration()->setSQLLogger(null);
    unset($guestMySql);
    gc_collect_cycles();

    unset($guest);
    gc_collect_cycles();

    $progressBar->advance();
}

$progressBar->finish();
$output->writeln("");
$output->writeln("<comment>-- fim --</comment>");
