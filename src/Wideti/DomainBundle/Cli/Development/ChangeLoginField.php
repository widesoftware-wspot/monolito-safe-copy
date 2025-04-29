<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Question\Question;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$input      = new \Symfony\Component\Console\Input\ArgvInput([]);
$output     = new \Symfony\Component\Console\Output\ConsoleOutput();
$question   = new \Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper();
$mongo      = $container->get('doctrine.odm.mongodb.document_manager');

$output->writeln("<info>Escolha uma das opções abaixo:</info>");
$output->writeln("<info>1) Pesquisar os clientes com telefone duplicado</info>");
$output->writeln("<info>2) Mudar o campo de Login de E-MAIL para TELEFONE</info>");

$option = $question->ask($input, $output, new Question('<info>: </info>', null));

$clients = ['prod'];

if ($option == 1) {
    optionOne($mongo, $output, $clients);
} elseif ($option == 2) {
    optionTwo($mongo, $output, $clients);
} else {
    $output->writeln("<info>==== Opcao nao encontrada! ====</info>\n");
}

function optionOne($mongo, $output, $clients)
{
    foreach ($clients as $client) {
        $mongoClient    = $mongo->getConnection()->getMongoClient();
        $clientDatabase = $client;
        $database       = $mongoClient->$clientDatabase;
        $collection     = $database->guests;

        $search = $collection->aggregate([
            [
                '$group' => [
                    '_id'               => [ 'phone' => '$properties.phone' ],
                    'repetidos'         => [ '$addToSet' => '$properties.email' ],
                    'total_repetidos'   => [ '$sum' => 1 ]
                ]
            ],
            [
                '$match' => [
                    'total_repetidos' => [ '$gte' => 2 ]
                ]
            ],
            [
                '$sort' => [ 'total_repetidos' => -1 ]
            ],
            [
                '$limit' => 10
            ]
        ]);

        $output->writeln("<info>==== Cliente ({$client}) selecionado ====</info>");

        $repetidos[$client] = [];

        foreach ($search['result'] as $key => $value) {
            if ($value['_id']['phone'] == '') {
                continue;
            }

            $output->writeln("<comment>Phone: {$value['_id']['phone']}</comment>");

            foreach ($value['repetidos'] as $values) {
                $output->writeln("<comment>E-mail: {$values}</comment>");
//                $collection->remove(['properties.email' => $values]);
            }
        }

        $output->writeln("<info>==== fim ====</info>\n");
    }
}

function optionTwo($mongo, $output, $clients)
{
    foreach ($clients as $client) {
        $output->writeln("<info>==== Cliente ({$client}) selecionado ====</info>");

        $mongoClient    = $mongo->getConnection()->getMongoClient();
        $clientDatabase = $client;
        $database       = $mongoClient->$clientDatabase;
        $fields         = $database->fields;
        $guests         = $database->guests;

        $fields->remove(
            [
                'identifier' => 'email'
            ]
        );

        $fields->update(
            [
                'identifier' => 'phone'
            ],
            [
                '$set' => [
                    'isUnique' => true,
                    'isLogin'  => true
                ]
            ]
        );

        $guests->deleteIndex('properties_email');

        $guests->ensureIndex(
            [
                'properties_phone' => 1
            ],
            [
                'unique'   => true,
                'name'     => 'properties_phone_1',
                'sparse'   => true
            ]
        );

        $output->writeln("<info>==== fim ====</info>\n");
    }
}
