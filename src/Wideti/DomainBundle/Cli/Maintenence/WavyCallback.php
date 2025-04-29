<?php
//php5.6 CreateSMSReport.php 9279 2018 10 01
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Wideti\ApiBundle\Helpers\Builder\SmsCallbackBuilder;
use Wideti\ApiBundle\Helpers\Dto\SmsCallbackDto;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application        = new Application($kernel);
$container          = $application->getKernel()->getContainer();
$output             = new ConsoleOutput();
$em                 = $container->get('doctrine')->getEntityManager('default');
$smsHistoryService  = $container->get('core.service.sms_history');

$results = getSmsWithoutCallbackStatus($em->getConnection());

if (empty($results)) {
    $output->writeln("<comment>Não há dados para processar</comment>");
    exit;
}

$ids = [];

foreach ($results as $result) {
    array_push($ids, $result['message_id']);
}

$smsCallback = getWavyCallback($ids);

foreach ($smsCallback as $item) {
    $smsDto = buildDto($item);
    $smsHistoryService->updateHistoryWithCallback($smsDto);
}

$output->writeln("<comment>Processo realizado com sucesso</comment>");

function getSmsWithoutCallbackStatus($connection)
{
    $statement = $connection->prepare("
        SELECT *
        FROM sms_historic
        WHERE sender = 'WAVY'
        AND message_status IS NULL;
    ");
    $statement->execute();
    return $statement->fetchAll();
}

function getWavyCallback($ids)
{
    $body = [
        "ids" => $ids
    ];

    $ch = curl_init("https://api-messaging.wavy.global/v1/sms/status/search");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "authenticationtoken: rS9z_TmwmKktR-zbX7X-NBssoCSL4_jvYrgBgwu6",
        "username: guilherme.rogieri@wspot.com.br",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    $response = curl_exec($ch);
    $statuses = json_decode($response, true);
    return $statuses["smsStatuses"];
}

function buildDto($callback)
{
    $builder = new SmsCallbackBuilder();

    return $builder
        ->withSender(SmsCallbackDto::SENDER_WAVY)
        ->withId($callback["correlationId"])
        ->withCarrierName(isset($callback["carrierName"]) ? $callback["carrierName"] : "N/I")
        ->withDestination($callback["destination"])
        ->withSentStatusCode(isset($callback["sentStatusCode"]) ? $callback["sentStatusCode"] : 0)
        ->withSentStatus($callback["sentStatus"])
        ->withDeliveredStatus(isset($callback["deliveredStatus"]) ? $callback["deliveredStatus"] : "N/I")
        ->withDeliveredDate(
            isset($callback["deliveredDate"])
                ? new \DateTime(date("Y-m-d H:i:s", strtotime($callback["deliveredDate"])))
                : new \DateTime("NOW")
        )
        ->build();
}