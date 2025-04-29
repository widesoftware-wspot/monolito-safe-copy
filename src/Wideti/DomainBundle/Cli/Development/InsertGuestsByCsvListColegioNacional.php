<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Wideti\DomainBundle\Helpers\StringHelper;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application    = new Application($kernel);
$container      = $application->getKernel()->getContainer();

$output         = new \Symfony\Component\Console\Output\ConsoleOutput();
$input          = new \Symfony\Component\Console\Input\ArgvInput();
$em             = $container->get('doctrine.orm.entity_manager');
$mongo          = $container->get('doctrine.odm.mongodb.document_manager');

$csvPathFile    = './alunos.csv';
$handle         = null;

try {
    $handle = fopen($csvPathFile, 'r');
} catch (Exception $e) {
    $output->writeln("<error>Arquivo não existe.</error>");
    exit;
}

const CODIGO           = 0;
const NOME             = 1;
const SENHA            = 2;

$client = $em
    ->getRepository('DomainBundle:Client')
    ->findOneBy([
        'domain' => 'nacionalnet'
    ]);

$totalGuests = (count(file($csvPathFile, FILE_SKIP_EMPTY_LINES)) - 1);
$progressBar = new \Symfony\Component\Console\Helper\ProgressBar($output, $totalGuests);
$progressBar->setBarCharacter('<fg=magenta>=</>');
$progressBar->setProgressCharacter("|");

$output->writeln("<info>{$totalGuests} visitantes encontrados</info>");

while (($data = fgetcsv($handle, null, ",")) !== false) {
    $nome           = $data[NOME];
    $password       = $data[SENHA];

    /**
     * MYSQL
     */
    $guestMySql = new \Wideti\DomainBundle\Entity\Guests();
    $guestMySql->setClient($client);
    $em->persist($guestMySql);
    $em->flush();

    /**
     * MONGODB
     */
    $mongoClient    = $mongo->getConnection()->getMongoClient();
    $clientDatabase = StringHelper::slugDomain($client->getDomain());
    $database       = $mongoClient->$clientDatabase;
    $collection     = $database->guests;

    $document = [
        'mysql'                     => $guestMySql->getId(),
        'password'                  => $password,
        'group'                     => 'guest',
        'status'                    => \Wideti\DomainBundle\Document\Guest\Guest::STATUS_ACTIVE,
        'emailIsValid'              => true,
        'locale'                    => "pt_br",
        'returning'                 => false,
        'registrationMacAddress'    => "F4-CF-E2-DE-A7-60",
        'created'                   => new \MongoDate(),
        'properties'                => [
            'code'              => $data[CODIGO],
            'name'              => utf8_encode(ucwords((strtolower($nome))))
        ]
    ];

    $collection->insert($document);

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

function removeSpecialCatacters($string)
{
    return preg_replace('/[^A-Za-z0-9]/', '', $string);
}
