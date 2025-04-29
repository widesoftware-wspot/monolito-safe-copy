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
$container  = $application->getKernel()->getContainer();

$mongo      = $container->get('doctrine.odm.mongodb.document_manager');

$em         = $container->get('doctrine')->getEntityManager('default');

$clientList = $em->getRepository('DomainBundle:Client')->findAll();

$clientWithoutPosition = [];

$fileClientsWithoutChange = fopen("client-not-affectred.txt", "w+");
$writeClientesWithoutChange = fwrite($fileClientsWithoutChange,"dominio\n");

$fileScriptChanges = fopen("fields-changed.csv", "w+");
$filesChanged = fwrite($fileScriptChanges,"domain;field_id;field_name;position\n");

if($clientList){
    foreach ($clientList as $client) {
        $mongoClient    = $mongo->getConnection()->getMongoClient();
        $clientDatabase = StringHelper::slugDomain($client->getDomain());
        $database       = $mongoClient->$clientDatabase;
        $collection     = $database->fields;
        $fieldsWithoutPosition = $collection->find(['position'=>[
                '$exists' => false
            ]
        ]);

        if($fieldsWithoutPosition->count() === 0){
            $writeClientesWithoutChange = fwrite($fileClientsWithoutChange,"{$client->getDomain()}\n");
        }

        $arrayOfFields = [];
        $count=1;
        foreach ($fieldsWithoutPosition as $fieldWithoutPosition){
            $id = new MongoId($fieldWithoutPosition['_id']);
            if($collection->update(['_id' => $id], ['$set'=> ['position'=> ($count * 10)]])){
                $filesChanged = fwrite($fileScriptChanges,
                    "{$client->getDomain()};{$fieldWithoutPosition['_id']};{$fieldWithoutPosition['identifier']};{$count}\n");
            }
            $count++;
        }

    }
}

fclose($fileClientsWithoutChange);
fclose($fileScriptChanges);








