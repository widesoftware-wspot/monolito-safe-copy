<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Wideti\DomainBundle\Helpers\StringHelper;

$env = 'prod';
$companySearch = 'sicred';
$emailTo = 'thiago.rodines@wideti.com.br';
$emailFrom = 'contato@wideti.com.br';

$kernel = new AppKernel($env, true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$input          = new \Symfony\Component\Console\Input\ArgvInput([]);
$output         = new \Symfony\Component\Console\Output\ConsoleOutput();
$question       = new \Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper();
$mongo          = $container->get('doctrine.odm.mongodb.document_manager');
$mailerService  = $container->get('core.service.mailer');
$em             = $container->get('doctrine.orm.entity_manager');
$emailHeader    = $container->get('core.service.email_header');
$mailer         = $container->get('core.service.mailer');

// Get Sicreds domain
$sicreds = $em->getRepository('DomainBundle:Client')
    ->createQueryBuilder('c')
    ->select('c')
    ->where('c.company LIKE :value')
    ->setParameter('value', "%$companySearch%")
    ->getQuery()
    ->getResult();


$domains = []; // list Sicred domains
foreach ($sicreds as $sicred)
{
    $domain = StringHelper::slugDomain($sicred->getDomain());
    $domains[] = $domain;
}


if ($env == 'prod') {
    $domains = ['prod'];
}

$domainWithoutSMSConfirmation = [];
$output->writeln("*** Início...");
foreach ($domains as $domain) {
    $mongoClient    = $mongo->getConnection()->getMongoClient();
    $clientDatabase = $domain;
    $database       = $mongoClient->$clientDatabase;
    $collection     = $database->configurations;

    $configurations = $collection->find();
    foreach($configurations as $conf)
    {
        $items = $conf["items"];
        $groupId = $conf["groupId"];

        foreach ($items as $item)
        {
            if($item["key"] == "confirmation_sms") {
                $value = (integer)$item["value"];
                if ($value == 0) {
                    $domainWithoutSMSConfirmation[] = $domain;
                }
            }
        }
    }
}

// Send email
if ($domainWithoutSMSConfirmation) {
    $domainWithoutSMSConfirmation = array_unique($domainWithoutSMSConfirmation);
    $sicreds = "";
    foreach ($domainWithoutSMSConfirmation as $domain)
    {
        $domain = "$domain.wspot.com.br";
        $sicreds = $sicreds . $domain . "<br>";
    }

    $builder = new \Wideti\DomainBundle\Service\Mailer\Message\MailMessageBuilder();
    $message = $builder
        ->subject('Sicred sem confirmação de SMS ' . date("d/m/Y H:i:s"))
        ->from(['WSpot' => $emailHeader->getSender()])
        ->to([[$emailTo]])
        ->htmlMessage(
            'Sicred sem confirmação de SMS <br>'
            .$sicreds
        )
        ->build()
    ;
    $mailer->send($message);

    $output->writeln(implode("\n", $domainWithoutSMSConfirmation));
    $output->writeln("*** Foi enviado um e-mail para $emailTo, com as Sicreds que não possuem SMS confirmado");
} else {
    $output->writeln("*** Nenhum dado encontrado");
}

$output->writeln("*** Fim ***");
