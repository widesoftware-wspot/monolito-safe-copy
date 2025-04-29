<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Wideti\DomainBundle\Helpers\StringHelper;

$kernel = new AppKernel('prod', true);
$kernel->boot();
$output = new \Symfony\Component\Console\Output\ConsoleOutput();
$application = new Application($kernel);
$container = $application->getKernel()->getContainer();

$em = $container->get('doctrine.orm.entity_manager');
$mongo = $container->get('doctrine.odm.mongodb.document_manager');

$rsm = new \Doctrine\ORM\Query\ResultSetMapping();
$rsm->addScalarResult('domain', 'domain');
$rsm->addScalarResult('client_id', 'id', "integer");

$sql = "SELECT DISTINCT client_id,domain 
        FROM radcheck 
        INNER JOIN clients 
        ON client_id = clients.id 
        WHERE attribute = 'Expiration'";

$query = $em->createNativeQuery($sql, $rsm);
$clientList = $query->getResult();

$fileName = "clientes_processados.txt";
$file = @fopen($fileName, "a+");

foreach ($clientList as $key => $client) {
    $clientDatabase = StringHelper::slugDomain($client["domain"]);
    $mongoDatabase = $mongo->getConnection()->getMongoClient()
        ->selectDB($clientDatabase);
    $clientId = $client["id"];
    $clientsExist = strpos(file_get_contents($fileName), $client["domain"]);

    if ($clientsExist !== false) {
        continue;
    }

    $sql = "SELECT  client_id,username 
            FROM radcheck 
            WHERE client_id = $clientId
            AND attribute = 'Expiration'";

    $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
    $rsm->addScalarResult('username', 'guestId', "integer");
    $rsm->addScalarResult('client_id', 'client_id', "integer");

    $query = $em->createNativeQuery($sql, $rsm);
    $radcheckGuests = $query->getResult();

    $output->writeln("INICIANDO ALTERAÇÃO DO CLIENTE: $clientDatabase");

    foreach ($radcheckGuests as $keyGuest => $radcheckGuest) {
        $guest = $mongoDatabase->selectCollection("guests")
            ->findOne(['mysql' => $radcheckGuest['guestId']]);
        $group = $mongoDatabase->selectCollection("groups")
            ->findOne(['shortcode' => $guest['group']]);

        $groupId = $group["_id"];
        $guestId = $radcheckGuest['guestId'];
        $client_id = $radcheckGuest['client_id'];

        if (!empty($guestId)) {
            $sql = "UPDATE radcheck SET group_id = '$groupId' WHERE username = $guestId AND client_id = $client_id AND attribute = 'Expiration'";
            $em->getConnection()->executeUpdate($sql);
            $output->writeln("Client $key - Inserido com sucesso e Visitante $keyGuest - Inserido com sucesso!");
            $output->writeln("Visitante: " . $guestId . " Cliente: "
                . $client_id);
        }

        unset($guestEntity);
        unset($guest);

        unset($groupEntity);
        unset($group);

        unset($groupId);
        unset($guestId);
        unset($client_id);

    }

    unset($radcheckGuest);
    unset($radcheckGuests);
    unset($rsm);


    @fwrite($file, $client["domain"]);
    $output->writeln("\n");
}
@fclose($file);
$output->writeln("---------------FIM--------------------");
