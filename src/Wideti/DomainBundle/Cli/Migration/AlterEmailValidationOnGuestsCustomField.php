<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;

$kernel      = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container   = $application->getKernel()->getContainer();
$output      = new \Symfony\Component\Console\Output\ConsoleOutput();
$em          = $container->get('doctrine')->getEntityManager('default');
$mongo       = $container->get('doctrine.odm.mongodb.document_manager');
$clients     = $em->getRepository('DomainBundle:Client')->findAll();

foreach ($clients as $client) {
    $mongoClient = $mongo->getConnection()->getMongoClient();
    $clientDatabase = \Wideti\DomainBundle\Helpers\StringHelper::slugDomain($client->getDomain());

    $output->writeln("<comment>Domínio: {$clientDatabase}</comment>");

    $mongoClient->$clientDatabase->fields->update(
        [
            'identifier' => 'email'
        ],
        [
            '$set' => [
                'validations' => [
                    [
                        'type' => 'required',
                        'value' => true,
                        'message' => 'wspot.signup_page.field_required',
                        'locale' => [
                            'pt_br',
                            'en',
                            'es'
                        ]
                    ],
                    [
                        'type' => 'email',
                        'value' => true,
                        'message' => 'wspot.signup_page.field_valid_email',
                        'locale' => [
                            'pt_br',
                            'en',
                            'es'
                        ]
                    ],
                    [
                        'type' => 'maxlength',
                        'value' => 256,
                        'message' => 'Este campo deve ter no máximo 256 caracteres',
                        'locale' => [
                            'pt_br',
                            'en',
                            'es'
                        ]
                    ]
                ]
            ]
        ]
    );

    $output->writeln("<comment>Processamento efetuado com sucesso.</comment>");
}
