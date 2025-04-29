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
$mongo      = $container->get('doctrine.odm.mongodb.document_manager');
$monolog    = $container->get('logger');

$file = $argv[1];


if (($handle = fopen("{$argv[1]}.csv", "r")) !== false)
{
    while (($data = fgetcsv($handle,null, ";")) !== false)
    {
        $newDocument = formatDocument($data[DOCUMENT]);
//        $guest = [
//            "password" => strlen($data[PASSWORD]) < 8 ? "0" . $data[PASSWORD] : $data[PASSWORD],
//            "group" => $groupId,
//            "properties" => [
//                "name" => utf8_encode(ucwords(strtolower($data[0]))),
//                "document" => $newDocument,
//                "data_nascimento" => $data[DATA_NASC]
//            ]
//        ];
//
//        $guestJson = json_encode($guest);
//
//        //post na API
//        $ch = curl_init($apiUrl . $guestsEndpoint);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_POST, 1);
//        curl_setopt($ch, CURLOPT_HTTPHEADER, [
//            'X-TOKEN: ' . $apiKey,
//            'Content-Type: application/json',
//            'Content-Length: ' . strlen($guestJson)
//        ]);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $guestJson);
//        $response = curl_exec($ch);
//
//        echo $response . PHP_EOL . PHP_EOL;

    }

    fclose($handle);
}



//$clientDomainFrom = $em->getRepository('DomainBundle:Client')->findBy(['domain' => $domainFrom]);
//$clientDomainTo = $em->getRepository('DomainBundle:Client')->findBy(['domain' => $domainTo]);
//
//$mongoClient    = $mongo->getConnection()->getMongoClient();
//$clientFromDatabase = $clientDomainFrom[0]->getDomain();
//$database       = $mongoClient->$clientFromDatabase;
//$collection = $database->selectCollection('guests');
//
//$cursor = $collection->find();
//
//$clientToDatabase = $clientDomainTo[0]->getDomain();
//
//foreach ($cursor as $document) {
//    if ($document['registrationMacAddress'] === $apMacAddressFrom) {
//        $mongoClient->$clientToDatabase->guests->insert($document);
//        $mongoClient->$clientFromDatabase->guests->remove($document);
//    }
//}
