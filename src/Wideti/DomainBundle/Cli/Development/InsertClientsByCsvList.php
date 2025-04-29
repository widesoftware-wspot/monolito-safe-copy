<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpFoundation\Session\Session;
use Wideti\DomainBundle\Entity\Client;
use Wideti\DomainBundle\Entity\Users;
use Wideti\DomainBundle\Service\User\UserService;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$output        = new \Symfony\Component\Console\Output\ConsoleOutput();
$input         = new \Symfony\Component\Console\Input\ArgvInput();
$em            = $container->get('doctrine.orm.entity_manager');
$clientService = $container->get('core.service.client');
$userService   = $container->get('core.service.user');
//$configService = $container->get('core.service.configuration');
$mongo         = $container->get('doctrine.odm.mongodb.document_manager');
$monolog       = $container->get('logger');

$question = new Question('<info>Caminho completo do CSV: </info>', null);
$questionHelper = new \Symfony\Component\Console\Helper\QuestionHelper();
$csvPathFile = trim($questionHelper->ask($input, $output, $question));

if (empty($csvPathFile)) {
    $output->writeln("<error>Caminho do arquivo deve ser informado!</error>");
    exit;
}

$handle = null;
try {
    $handle = fopen($csvPathFile, 'r');
} catch (Exception $e) {
    $output->writeln("<error>Arquivo não existe.</error>");
    exit;
}

checkIfCSVIsValid($csvPathFile, $output);

const COMPANY      = 0;
const DOCUMENT     = 1;
const ADDRESS      = 2;
const DISTRICT     = 3;
const CITY         = 4;
const STATE        = 5;
const ZIP_CODE     = 6;
const PHONE        = 7;
const DOMAIN       = 8;
const REDIRECT_URL = 9;
const CLOSING_DATE = 10;
const SMS_COST     = 11;
const NUMBER_APS   = 12;
const ADMIN_NAME   = 13;
const ADMIN_EMAIL  = 14;
const ADMIN_PWD    = 15;

$clients        = [];
$users          = [];
$redirectUrls   = [];

while (($data = fgetcsv($handle, null, ",")) !== false) {
    $client = new Client();
    $client->setCompany($data[COMPANY]);
    $client->setDocument($data[DOCUMENT]);
    $client->setAddress($data[ADDRESS]);
    $client->setDistrict($data[DISTRICT]);
    $client->setCity($data[CITY]);
    $client->setState($data[STATE]);
    $client->setZipCode($data[ZIP_CODE]);
    $client->setDomain($data[DOMAIN]);
    $client->setStatus(Client::STATUS_POC);
    $client->setClosingDate($data[CLOSING_DATE]);
    $client->setSmsCost($data[SMS_COST]);
    $client->setContractedAccessPoints($data[NUMBER_APS]);
    $clients[] = $client;

    $username = empty($data[ADMIN_EMAIL]) ? "" : $data[ADMIN_EMAIL];
    $name = empty($data[ADMIN_NAME]) ? "" : $data[ADMIN_NAME];
    if (!empty($username) && !empty($name)) {
        $user = new Users();
        $user->setUsername($username);
        $user->setNome($name);
        $user->setPassword($data[ADMIN_PWD]);
        $users[] = $user;
    } else {
        $users[] = null;
    }

    $redirectUrls[] = $data[REDIRECT_URL];
}

$output->writeln("<info>Criando clientes aguarde...</info>");

$domainsNotCreated = [];
/**
 * @var Client $client
 * @var Users $adminUser
 */
for ($actual = 0; $actual < count($clients); $actual++) {
    $client = $clients[$actual];
    $adminUser = $users[$actual];

    $domainExists = domainExists($client->getDomain(), $em);
    if ($domainExists) {
        $domainsNotCreated[] = $domainExists;
        continue;
    }

    $modules = $em
        ->getRepository('DomainBundle:Module')
        ->findBy([
            'shortCode' => ['campaign', 'blacklist', 'access_code','business_hours']
        ]);

    foreach ($modules as $module) {
        $client->addModule($module);
    }

    $session = new Session();
    $session->set('wspotClient', $client);
    $container->set('session', $session);

    $client->setStatus(Client::STATUS_ACTIVE);
    $clientService->create($client);

    if (!empty($adminUser)) {
        $data = [
            'partner_name'  => $client->getCompany(),
            'from_email'    => $adminUser->getUsername(),
            'redirect_url'  => $redirectUrls[$actual],
            'lead_name'     => $adminUser->getNome(),
            'password'      => $adminUser->getPassword() ?: null
        ];

        $adminUser = createAdminUser($client, $data, $em, $userService);
    }

    $em->flush();
}

$output->writeln("<info>Dominios duplicados não inseridos:</info>");
foreach ($domainsNotCreated as $domain) {
    $output->writeln("<info>{$domain}</info>");
}

function domainExists($domain, EntityManager $em)
{
    $client = $em
        ->getRepository("DomainBundle:Client")
        ->findOneBy([
            "domain" => $domain
        ]);

    if (!$client) {
        return false;
    }

    return $client->getDomain();
}

function createAdminUser(Client $client, $data, EntityManager $em, UserService $userService)
{
    $user           = new Users();
    $email          = $data['from_email'];
    $leadName       = $data['lead_name'];
    $password       = $data['password'];
    $role           = $em
        ->getRepository("DomainBundle:Roles")
        ->find(Users::ROLE_ADMIN);

    $user->setUsername($email);
    $user->setPassword($password);
    $user->setNome($leadName);
    $user->setStatus(Users::ACTIVE);
    $user->setReceiveReportMail(true);
    $user->setReportMailLanguage('pt_br');
    $user->setFinancialManager(false);
    $user->setRole($role);
    $user->setClient($client);

    $userService->register($user, $password ? false : true);
    return $user;
}

function checkIfCSVIsValid($csvPathFile, \Symfony\Component\Console\Output\ConsoleOutput $output)
{
    $handle = null;
    try {
        $handle = fopen($csvPathFile, 'r');
    } catch (Exception $e) {
        $output->writeln("<error>Arquivo não existe.</error>");
        exit;
    }

    $line = 1;
    while (($data = fgetcsv($handle, null, ",")) !== false) {
        if (count($data) != 16) {
            $output->writeln("<error>Linha {$line} não possui os dados válidos.</error>");
            exit;
        }
        $line++;
    }

    fclose($handle);
}
