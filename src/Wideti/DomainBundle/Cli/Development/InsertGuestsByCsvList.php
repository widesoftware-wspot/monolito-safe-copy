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

//$csvPathFile    = 'src/Wideti/DomainBundle/Cli/Development/all2.csv';
$csvPathFile    = '/tmp/all2.csv';
$handle         = null;

try {
    $handle = fopen($csvPathFile, 'r');
} catch (Exception $e) {
    $output->writeln("<error>Arquivo não existe.</error>");
    exit;
}

checkIfCSVIsValid($csvPathFile, $output);

const DOCUMENTO = 0;
const NOME      = 1;

$client = $em
    ->getRepository('DomainBundle:Client')
    ->findOneBy([
        'domain' => 'kopclub'
    ]);

$totalGuests = (count(file($csvPathFile, FILE_SKIP_EMPTY_LINES)) - 1);
$progressBar = new \Symfony\Component\Console\Helper\ProgressBar($output, $totalGuests);
$progressBar->setBarCharacter('<fg=magenta>=</>');
$progressBar->setProgressCharacter("|");

$output->writeln("<info>{$totalGuests} visitantes encontrados</info>");

while (($data = fgetcsv($handle, null, ",")) !== false) {
    $nome       = $data[NOME];
    $documento  = $data[DOCUMENTO];

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
        'password'                  => 'kopclub',
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
            'name'              => $nome,
            'document'          => $documento,
            'email'             => "{$guestMySql->getId()}@wspot.com.br",
            'data_nascimento'   => new \MongoDate(strtotime('2000-11-11 00:00:00')),
            'mobile'            => '12345678900'
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

function checkIfCSVIsValid($csvPathFile, \Symfony\Component\Console\Output\ConsoleOutput $output)
{
    $handle = null;
    try {
        $handle = fopen($csvPathFile, 'r');
    } catch (Exception $e) {
        $output->writeln("<error>Arquivo não existe.</error>");
        exit;
    }

    fclose($handle);
}
