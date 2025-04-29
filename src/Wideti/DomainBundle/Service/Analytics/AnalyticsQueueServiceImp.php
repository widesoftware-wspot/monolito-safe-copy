<?php

namespace Wideti\DomainBundle\Service\Analytics;

use Monolog\Logger;
use Wideti\DomainBundle\Service\Queue\Message;
use Wideti\DomainBundle\Service\Queue\QueueService;

class AnalyticsQueueServiceImp implements AnalyticsQueueService
{
	private $awsKey;
	private $awsSecret;
	private $awsSqsRegion;
	private $awsSqsName;

	/**
	 * @var QueueService
	 */
	private $sqs;
	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * AnalyticsQueueServiceImp constructor.
	 * @param $key
	 * @param $secret
	 * @param $awsSqsRegion
	 * @param $awsSqsName
	 * @param Logger $logger
	 */
	public function __construct($key, $secret, $awsSqsRegion, $awsSqsName, Logger $logger)
	{
		$this->awsKey       = $key;
		$this->awsSecret    = $secret;
		$this->awsSqsRegion = $awsSqsRegion;
		$this->awsSqsName   = $awsSqsName;
		$this->logger       = $logger;
		$this->sqs          = new QueueService($this->awsKey, $this->awsSecret, $this->awsSqsRegion, $this->awsSqsName);
	}

	public function sendToQueue($content)
	{
		$message = new Message();
		$message->setContent(json_encode($content));

		try {
			$this->sqs->sendMessage($message);
		} catch (\Exception $exception) {
			$this->logger->addCritical("SQS Message error: {$exception->getMessage()}");
		}
	}
}
