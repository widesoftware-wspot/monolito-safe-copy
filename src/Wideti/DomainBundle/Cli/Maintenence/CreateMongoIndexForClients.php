<?php
/**
 * Created by PhpStorm.
 * User: wideti
 * Date: 22/01/19
 * Time: 10:32
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Wideti\DomainBundle\Helpers\StringHelper;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$output     = new \Symfony\Component\Console\Output\ConsoleOutput();

$mongo      = $container->get('doctrine.odm.mongodb.document_manager');
$monolog    = $container->get('logger');
$em         = $container->get('doctrine')->getEntityManager('default');

$clientList = $em->getRepository('DomainBundle:Client')->findAll();

$file = fopen("Clientes_com_indices_adicionados.csv", "w+");
$write = fwrite($file, "client_id; domain \n");


if (!is_null($clientList)) {
    foreach ($clientList as $client) {
        $mongoClient            = $mongo->getConnection()->getMongoClient();
        $clientDatabase         = $client->getDomain();
        $clientDatabase         = StringHelper::slugDomain($clientDatabase);
        $database               = $mongoClient->$clientDatabase;
        $collectionConfig       = $database->fields;
        $collection             = $database->guests;

        $loginFieldDocument = $collectionConfig->find([
            'isLogin' => [
                '$eq' => true
            ]
        ]);


        if (count($loginFieldDocument) === 1) {
            foreach ($loginFieldDocument as $docuement) {
                $loginField = $docuement['identifier'];
            }
        }

        $collection->createIndex(['created' => 1], ['background' => true]);
        $collection->createIndex(['social.id' => 1], ['background' => true]);
        $collection->createIndex(["properites.{$loginField}" => 1], ['background' => true]);

        $write = fwrite($file, "{$client->getId()};{$client->getDomain()}\n");

    }

    fclose($file);
}
