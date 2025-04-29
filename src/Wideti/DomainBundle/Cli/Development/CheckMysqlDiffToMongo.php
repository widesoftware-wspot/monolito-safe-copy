<?php
require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Wideti\DomainBundle\Helpers\StringHelper;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$output     = new \Symfony\Component\Console\Output\ConsoleOutput();
$em         = $container->get('doctrine.orm.entity_manager');
$mongo      = $container->get('doctrine.odm.mongodb.document_manager');
$monolog    = $container->get('logger');
$params     = $argv;

if (isset($params[1])) {

    $clients = $em->getRepository("DomainBundle:Client")
        ->findById($params[1])
    ;

    if ($clients == null) {
        echo PHP_EOL;
        echo "Cliente com id ".$params[1]." não encontrado";
        echo PHP_EOL;
        exit;
    }
} else {
    $clients = $em->getRepository("DomainBundle:Client")
        ->findAll()
    ;
}

foreach ($clients as $client) {

    echo PHP_EOL;
    echo "- Analisando diferença do cliente " . $client->getDomain();
    echo PHP_EOL;

    $guests = $em->getRepository('DomainBundle:Guests')
        ->findByClient($client)
    ;

    echo "- " . count($guests) . " visitantes encontrados";
    echo PHP_EOL;

    $missing = [];
    $failed  = [];
    $existsWithSameId = [];
    $existsDiffId = [];

    foreach ($guests as $guest) {

        $mongoClient    = $mongo->getConnection()->getMongoClient();
        $clientDatabase = StringHelper::slugDomain($client->getDomain());
        $database       = $mongoClient->$clientDatabase;
        $collection     = $database->guests;

        $exists         = $collection->findOne([
            "email" => $guest->getEmail()
        ]);

        if (is_null($exists)) {
            $missing[] = $guest->getEmail();
        } else {
            if ($guest->getId() == $exists["mysql"]) {
                $existsWithSameId[] = $guest->getEmail();
            } else {

                try {
                    $update = $collection->update(["email" => $guest->getEmail()], [
                        '$set' => [
                            "mysql" => $guest->getId()
                        ]
                    ]);
                } catch (\Exception $e) {
                    echo "----- Falha ao corrigir: " . $guest->getEmail() . " -- \"" . $e->getMessage() . "\"";
                }
                $existsDiffId[] = $guest->getEmail();
            }
        }
    }

    if (count($existsWithSameId) > 0) {
        echo "--- " . count($existsWithSameId) . " estão corretos";
        echo PHP_EOL;
    } else {
        echo "- Nenhum visitante correto encontrado";
        echo PHP_EOL;
    }

    if (count($existsDiffId) > 0) {
        echo "--- " . count($existsDiffId) . " estão com id's diferentes no Mongo e foram corrigidos";
        echo PHP_EOL;
        foreach ($existsDiffId as $existsDiffIdRow) {
            echo "----- " . $existsDiffIdRow;
            echo PHP_EOL;
        }
        echo PHP_EOL;
    } else {
        echo "- Nenhum visitante com ids trocados encontrado";
        echo PHP_EOL;
    }

    if (count($missing) > 0) {
        echo "--- " . count($missing) . " que estão no MySQL mas NÃO estão no MongoDB foram removidos";
        echo PHP_EOL;
        foreach ($missing as $miss) {
            try {
                $deleteGuests = $em->getRepository("DomainBundle:Guests")
                    ->findOneByEmail($miss);

                $em->remove($deleteGuests);
                $em->flush();
            } catch (\Exception $e) {
                echo "----- Falha ao remover: " . $miss . " -- \"" . $e->getMessage() . "\"";
            }
            echo "----- " . $miss;
            echo PHP_EOL;
        }

        echo PHP_EOL;
    } else {
        echo "- Nenhum visitante faltando encontrado";
        echo PHP_EOL;
    }

    echo PHP_EOL;
    echo "-----------------------------------------------------------------";
    echo PHP_EOL;
}
