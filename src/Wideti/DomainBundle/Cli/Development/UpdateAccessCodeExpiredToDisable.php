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

foreach ($clients as $client) {
    $mongoClient    = $mongo->getConnection()->getMongoClient();
    $clientDatabase = StringHelper::slugDomain($client->getDomain());
    $database       = $mongoClient->$clientDatabase;

    $database->accessCode->update(
        [
            'periodTo' => [
                '$lte' => new \MongoDate(strtotime(date('Y-m-d H:i:s')))
            ]
        ],
        [
            '$set' => [
                'enable' => false
            ]
        ],
        [
            'multiple' => true
        ]
    );
}

/**
 * @param \Doctrine\ORM\EntityManager $em
 * @return array|\Wideti\DomainBundle\Entity\Client[]
 */
function getClients(\Doctrine\ORM\EntityManager $em) {
    return $em->getRepository('DomainBundle:Client')->findAll();
}
