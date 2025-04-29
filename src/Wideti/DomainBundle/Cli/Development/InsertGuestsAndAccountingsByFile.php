<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application    = new Application($kernel);
$container      = $application->getKernel()->getContainer();

$output         = new \Symfony\Component\Console\Output\ConsoleOutput();
$connection     = $container->get('doctrine')->getEntityManager('default')->getConnection();
$mongo          = $container->get('doctrine.odm.mongodb.document_manager');
$elastic        = $container->get('core.service.elastic_search');

$pathFile   = 'visitantes.txt';
//$pathFile   = 'acct_2019_08.txt';
$handle     = null;

try {
    $handle = fopen($pathFile, 'r');
} catch (Exception $e) {
    $output->writeln("<error>Arquivo não existe.</error>");
    exit;
}

//cargaDeDadosNoMySQL($connection, $handle);
cargaDeDadosNoMongo($mongo, $handle);
//cargaDeDadosNoElastic($elastic, $handle);

function cargaDeDadosNoMySQL($connection, $handle)
{
    $clientIdOnMySQL = 1;
    while (!feof($handle)) {
        $data = json_decode(fgets($handle), true);
        $query     = "INSERT INTO visitantes VALUES ({$data->mysql}, $clientIdOnMySQL)";
        $statement = $connection->prepare($query);
        $statement->execute();
    }
}

function cargaDeDadosNoMongo($mongo, $handle)
{
    $database = 'prod';
    while (!feof($handle)) {
        $data = json_decode(fgets($handle), true);

        $mongoClient    = $mongo->getConnection()->getMongoClient();
        $database       = $mongoClient->$database;
        $collection     = $database->guests;

        $document = [
            'mysql'                     => $data['mysql'],
            'password'                  => $data['password'],
            'group'                     => $data['group'],
            'status'                    => $data['status'],
            'emailIsValid'              => $data['emailIsValid'],
            'locale'                    => $data['locale'],
            'returning'                 => $data['returning'],
            'registrationMacAddress'    => $data['registrationMacAddress'],
            'created'                   => new \MongoDate($data['created']['sec']),
            'lastAccess'                => new \MongoDate($data['lastAccess']['sec']),
            'documentType'              => $data['documentType'],
            'registerMode'              => $data['registerMode'],
            'timezone'                  => 'America/Sao_Paulo',
            'properties'                => [
                'email' => $data['properties']['email'],
                'name' => $data['properties']['name'],
                'data_nascimento' => new \MongoDate($data['properties']['data_nascimento']['sec']),
                'phone' => $data['properties']['phone']
            ]
        ];

        $collection->insert($document);
    }
}

function cargaDeDadosNoElastic($elastic, $handle)
{
    while (!feof($handle)) {
        $data = json_decode(fgets($handle), true);

        $data['_source']['client_id'] = 1;
        $accounting = $data['_source'];

        $accounting['acctuniqueid'] = $accounting['acctuniqueid'] . random_int(0, 9);

        $accounting['acctstarttime']    = "2019-08-10 10:00:00";
        $accounting['acctstoptime']     = "2019-08-10 10:00:00";
        $accounting['interim_update']   = "2019-08-10 10:00:00";

        $elastic->index('radacct', $accounting, null, $data['_index']);
    }
}
