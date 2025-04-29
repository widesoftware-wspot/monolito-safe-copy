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

$em                     = $container->get('doctrine.orm.entity_manager');
$mongo                  = $container->get('doctrine.odm.mongodb.document_manager');
$sendAcctProcessor      = $container->get('core.service.guest_to_acct_processor_send');
$removeAcctProcessor    = $container->get('core.service.guest_to_acct_processor_remove');

$output->writeln("<info>Escolha uma das opções abaixo:</info>");
$output->writeln("<info>1) Enviar</info>");
$output->writeln("<info>2) Excluir</info>");

$option = $question->ask($input, $output, new Question('<info>: </info>', null));

$client = $em->getRepository('DomainBundle:Client')->findOneBy(['domain' => 'prod']);

if ($option == 1) {
    $guest = new \Wideti\DomainBundle\Document\Guest\Guest();
    $guest->setId("5df8dbfc93be540f008b4567");
    $guest->setMysql(1);
    $guest->setGroup('guest');
    $guest->setPassword('leonardo');
    $guest->setCreated(new MongoDate(strtotime('2019-12-19 10:00:00')));
    $guest->setStatus(1);
    $guest->setEmailIsValid(true);
    $guest->setEmailIsValidDate(new MongoDate(strtotime('2019-12-19 10:00:00')));
    $guest->setLocale('pt_br');
    $guest->setDocumentType('CPF');
    $guest->setRegistrationMacAddress('11-11-11-11-11-11');
    $guest->setReturning(true);
    $guest->setRegisterMode("Formulário");
    $guest->setNasVendor('mikrotik');
    $guest->setTimezone('America/Sao_Paulo');
    $guest->setValidated(new MongoDate(strtotime('2019-12-19 10:00:00')));
    $guest->setLastAccess(new MongoDate(strtotime('2019-12-19 10:00:00')));
    $guest->setProperties([
        "name" => "Leonardo",
        "email" => "fuzetolhf2@gmail.com",
        "mobile" => 19981848857,
        "data_nascimento" => new MongoDate(strtotime('1992-12-19 10:00:00')),
        "document" => "26375336090"
    ]);

    $sendAcctProcessor->process($client, $guest);
} elseif ($option == 2) {
    $removeAcctProcessor->process($client, 1);
} else {
    $output->writeln("<info>==== Opção não encontrada! ====</info>\n");
}
