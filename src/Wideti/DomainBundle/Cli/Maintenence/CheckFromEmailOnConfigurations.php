<?php
@error_reporting(E_ALL);
@ini_set('display_errors', 1);

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Helpers\StringHelper;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container   = $application->getKernel()->getContainer();
$output      = new \Symfony\Component\Console\Output\ConsoleOutput();
$mongo       = $container->get('doctrine.odm.mongodb.document_manager');
$em          = $container->get('doctrine')->getEntityManager('default');
$clients     = $em->getRepository('DomainBundle:Client')->findAll();
$data        = '';
$mailToCheck = [];
$status      = [ 0 => "Inativo", 1 => "Ativo", 2 => "PoC" ];

if (!isset($argv[1])) {
    $output->writeln("<comment>E-mail must be informed as a parameter</comment>");
    exit;
}

for ($i = 1; $i < @sizeof($argv); $i++) {
    $mailToCheck[] = $argv[$i];
}

foreach ($clients as $client) {
    $admin = $em->getRepository('DomainBundle:Users')->findOneBy([
        'client' => $client,
        'role'   => Users::ROLE_ADMIN
    ]);

    if ($admin) {
        $mongoClient    = $mongo->getConnection()->getMongoClient();
        $clientDatabase = $client->getDomain();
        $clientDatabase = StringHelper::slugDomain($clientDatabase);
        $database       = $mongoClient->$clientDatabase;

        foreach ($mailToCheck as $mail) {
            $configurations = $database->configurations->find([
                'items' => [
                    '$elemMatch' => [
                        '$and' => [
                            [ 'key'   => 'from_email' ],
                            [ 'value' => $mail ]
                        ]
                    ]
                ]
            ]);

            if ($configurations->count() > 0) {
                $database->configurations->update(
                    [
                        'items' => [
                            '$elemMatch' => [
                                '$and' => [
                                    ['key' => 'from_email'],
                                    ['value' => $mail]
                                ]
                            ]
                        ]
                    ],
                    [
                        '$set' => ['items.$.value' => $admin->getUsername()]
                    ],
                    [
                        'multiple' => true
                    ]
                );

                $data .= "{$client->getDomain()};{$status[$client->getStatus()]};" .
                    "{$mail};{$admin->getUsername()}\n";
            }
        }

        @reset($mailToCheck);
    }
}

if (strlen($data) > 0) {
    $output->writeln("<comment>{$data}</comment>");
    exit;
}

$output->writeln("<comment>Nothing was found.</comment>");
