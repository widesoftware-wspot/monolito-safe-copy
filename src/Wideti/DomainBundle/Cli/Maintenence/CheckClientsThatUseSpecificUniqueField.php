<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Wideti\DomainBundle\Helpers\StringHelper;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container   = $application->getKernel()->getContainer();
$output      = new \Symfony\Component\Console\Output\ConsoleOutput();
$mongo       = $container->get('doctrine.odm.mongodb.document_manager');
$em          = $container->get('doctrine')->getEntityManager('default');
$clients     = getClients($em);

if (!isset($argv[1])) {
    $output->writeln("<comment>Identifier do campo à procurar deve ser informado como argumento.</comment>");
    exit;
}

foreach ($clients as $client) {
    $mongoClient    = $mongo->getConnection()->getMongoClient();
    $clientDatabase = $client->getDomain();
    $clientDatabase = StringHelper::slugDomain($clientDatabase);
    $database       = $mongoClient->$clientDatabase;
    $fields = $database->fields->find([ 'isUnique' => true ]);

    if ($fields) {
        foreach ($fields as $field) {
            if ($field['identifier'] == $argv[1]) {
                $total = totalGuests($client, $em);
                echo "{$client->getDomain()} -> {$total}\n";
            }
        }
    }
}

function totalGuests(\Wideti\DomainBundle\Entity\Client $client, \Doctrine\ORM\EntityManager $em) {
    $guests = $em->getRepository('DomainBundle:Guests')->findBy([
        'client' => $client
    ]);

    return count($guests);
}

/**
 * @param \Doctrine\ORM\EntityManager $em
 * @return array|\Wideti\DomainBundle\Entity\Client[]
 */
function getClients(\Doctrine\ORM\EntityManager $em) {
    return $em->getRepository('DomainBundle:Client')->findAll();
}
