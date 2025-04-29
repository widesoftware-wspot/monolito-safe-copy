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
$em         = $container->get('doctrine.orm.entity_manager');
$mongo      = $container->get('doctrine.odm.mongodb.document_manager');

$output->writeln('<comment>==== Gerador de sms_historic ====</comment>');

$domain = $question->ask($input, $output, new Question('<info>Digite o domínio do cliente (string): </info>', null));

$client = $em->getRepository('DomainBundle:Client')->findOneBy([
    'domain' => $domain
]);

if (empty($client)) {
    $output->writeln("<comment>Cliente \"{$domain}\" não existe</comment>");
    exit;
}

$totalSms    = $question->ask($input, $output, new Question('<info>Total SMS por visitante (int): </info>', null));
$totalMonths = $question->ask($input, $output, new Question('<info>Qtd. meses a partir de hoje (int): </info>', null));

// Pega todos visitantes
$guests = $em->getRepository('DomainBundle:Guests')
    ->findAll();

$output->writeln("<comment>Gerando sms_historic, aguarde pode levar alguns minutos...</comment>");

//gera sms_historic
generateAccounting($em, $client, $guests, $totalSms, $totalMonths);

function generateAccounting(
    \Doctrine\ORM\EntityManager $em,
    \Wideti\DomainBundle\Entity\Client $client,
    $guests = [],
    $limitSms = 100,
    $totalMonths = 12
) {
    foreach ($guests as $guest) {
        $totalOfSms = rand(0, $limitSms);

        for ($current = 0; $current <= $totalOfSms; $current++) {
            $date = date("Y-m-d H:i:s", mt_rand(strtotime("-" . $totalMonths ." months"), time()));

            $sms = new \Wideti\DomainBundle\Entity\SmsHistoric();
            $sms->setGuest($guest);
            $sms->setMessageStatus('queued');
            $sms->setMessageId('243h5g4f412h');
            $sms->setBodyMessage('teste');
            $sms->setSmsCost('0,24');
            $sms->setSentTo('5519981848857');
            $sms->setSentDate($date);

            $em->persist($sms);
            $em->flush();
        }
    }
}
