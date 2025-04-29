<?php

namespace Wideti\DomainBundle\Service\Queue;

use Aws\Credentials\Credentials;
use Aws\Sqs\SqsClient;
use Doctrine\Common\Collections\ArrayCollection;
use Guzzle\Service\Resource\Model;

class QueueService
{
    /**
     * @var SqsClient
     */
    protected $sqs;

    /**
     * @var Model
     */
    protected $queue;

    public function __construct($key, $secret, $queueRegion, $queueName)
    {
        $credentials = new Credentials($key, $secret);

        $this->sqs = new SqsClient([
            'version' => '2012-11-05',
            'region'      => $queueRegion,
            'credentials' => $credentials
        ]);

        $this->sqsBaseUrl ='https://sqs.sa-east-1.amazonaws.com/769177069788/';
        $this->queueUrl = $this->sqsBaseUrl . $queueName;

    }

    public function getTotalMessages()
    {
        $queue = $this->sqs->getQueueAttributes([
            'QueueUrl' => $this->queueUrl,
            'AttributeNames' => ['ApproximateNumberOfMessages']
        ]);

        $attributes = $queue->get('Attributes');

        return (int)$attributes['ApproximateNumberOfMessages'];
    }

    public function receiveMessage()
    {
        $model = $this->sqs->receiveMessage([
            'QueueUrl'              => $this->queueUrl,
            'MaxNumberOfMessages'   => 10
        ]);

        $collection = new ArrayCollection();
        $messages   = $model->get('Messages');

        if (count($messages) == 0) {
            return $messages;
        }

        foreach ($messages as $row) {

            $message = new Message();
            $message->setId($row['MessageId']);
            $message->setContent($row['Body']);
            $message->setReceipt($row['ReceiptHandle']);

            $collection->add($message);
        }

        return $collection;
    }

    public function deleteMessage(Message $message)
    {
        $delete = $this->sqs->deleteMessage([
            'QueueUrl'      => $this->queueUrl,
            'ReceiptHandle' => $message->getReceipt()
        ]);

        return $delete;
    }

    public function sendMessage(Message $message)
    {
        try {
            $newMessage = $this->sqs->sendMessage([
                'QueueUrl'    => $this->queueUrl,
                'MessageBody' => $message->getContent()
            ]);

            return $newMessage;
        } catch (\Exception $exception) {
            throw new \Exception("SQS Send Message Error: {$exception->getMessage()}");
        }
    }

    public function sendMessageAsJson(Message $message)
    {
        try {
            $newMessage = $this->sqs->sendMessage([
                'QueueUrl'    => $this->queueUrl,
                'MessageBody' => $message->getContent(),
                'MessageAttributes' => [
                    'contentType' => [
                        'DataType' => 'String',
                        'StringValue' => 'application/json'
                    ]
                ]
            ]);

            return $newMessage;
        } catch (\Exception $exception) {
            throw new \Exception("SQS Send Message Error: {$exception->getMessage()}");
        }

    }
}
