<?php
//php5.6 CreateSMSReport.php 9279 2018 10 01
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container   = $application->getKernel()->getContainer();
$output      = new ConsoleOutput();
$em          = $container->get('doctrine')->getEntityManager('default');

$connection  = $em->getConnection();

if (!isset($argv[1])) {
    $output->writeln("<comment>ID do cliente deve ser informado.</comment>");
    exit;
}

$condition = "client_id = {$argv[1]} ";

if (isset($argv[2])) {
    $condition .= "AND YEAR(sent_date) = '{$argv[2]}' ";
}

if (isset($argv[3])) {
    $condition .= "AND MONTH(sent_date) = '{$argv[3]}' ";
}

if (isset($argv[4])) {
    $condition .= "AND DAY(sent_date) = '{$argv[4]}' ";
}

$statement = $connection->prepare("
    SELECT DATE_FORMAT(sent_date, '%d/%m/%Y %H:%i:%s') 'sent',
	       sent_to,
	       body_message
      FROM sms_historic
     WHERE {$condition}
  ORDER BY sent_date;
");

$statement->execute();

if ($statement->rowCount() == 0) {
    $output->writeln("<comment>Não há dados para os parâmetros especificados.</comment>");
    exit;
}

$result = $statement->fetchAll();
$file   = @fopen("SMSReport.csv", "w+");

@fwrite($file, "Enviado Em;Celular;Mensagem;\n");

foreach ($result as $data) {
    @fwrite($file, "{$data["sent"]};{$data["sent_to"]};{$data["body_message"]};\n");
}

@fclose($file);